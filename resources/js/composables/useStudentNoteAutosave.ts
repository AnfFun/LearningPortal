import { useHttp } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { store as storeNote, update as updateNote } from '@/routes/notes';
import type { StudentNote } from '@/types';

export type NoteAutosaveStatus =
    'idle' | 'pending' | 'saving' | 'saved' | 'error';

type NoteDraft = {
    persistedNoteId: number | null;
    studentNoteGroupId: string;
    title: string;
    body: string;
};

type NoteSnapshot = {
    studentNoteGroupId: number;
    title: string;
    body: string;
};

type NoteRequest = {
    student_note_group_id: number;
    title: string;
    body: string;
};

type NoteAutosaveResponse = {
    note: StudentNote;
};

const AUTOSAVE_DELAY = 800;

export function useStudentNoteAutosave(
    currentTeamSlug: MaybeRefOrGetter<string>,
) {
    const draft = reactive<NoteDraft>({
        persistedNoteId: null,
        studentNoteGroupId: '',
        title: '',
        body: '',
    });
    const status = ref<NoteAutosaveStatus>('idle');
    const latestSavedNote = ref<StudentNote | null>(null);
    const lastSavedFingerprint = ref('');
    const request = useHttp<NoteRequest, NoteAutosaveResponse>({
        student_note_group_id: 0,
        title: '',
        body: '',
    });

    let autosaveTimer: ReturnType<typeof setTimeout> | null = null;
    let sessionId = 0;
    let saveQueue: Promise<boolean> = Promise.resolve(true);

    function snapshot(): NoteSnapshot {
        return {
            studentNoteGroupId: Number(draft.studentNoteGroupId),
            title: draft.title,
            body: draft.body,
        };
    }

    function snapshotFromNote(note: StudentNote): NoteSnapshot {
        return {
            studentNoteGroupId: note.studentNoteGroupId,
            title: note.title,
            body: note.body,
        };
    }

    function fingerprint(value: NoteSnapshot): string {
        return JSON.stringify(value);
    }

    function hasMeaningfulContent(value: NoteSnapshot): boolean {
        return value.title.trim() !== '' || value.body.trim() !== '';
    }

    const hasUnsavedChanges = computed(() => {
        const current = snapshot();

        if (draft.persistedNoteId === null && !hasMeaningfulContent(current)) {
            return false;
        }

        return fingerprint(current) !== lastSavedFingerprint.value;
    });

    const isSaving = computed(
        () => status.value === 'pending' || status.value === 'saving',
    );
    const errors = computed(() => request.errors);

    function clearAutosaveTimer(): void {
        if (autosaveTimer === null) {
            return;
        }

        clearTimeout(autosaveTimer);
        autosaveTimer = null;
    }

    function resetRequestState(): void {
        clearAutosaveTimer();
        request.resetAndClearErrors();
        latestSavedNote.value = null;
    }

    function startCreate(groupId: number): void {
        sessionId++;
        resetRequestState();

        Object.assign(draft, {
            persistedNoteId: null,
            studentNoteGroupId: groupId.toString(),
            title: '',
            body: '',
        });

        lastSavedFingerprint.value = fingerprint(snapshot());
        status.value = 'idle';
    }

    function startEdit(note: StudentNote): void {
        sessionId++;
        resetRequestState();

        Object.assign(draft, {
            persistedNoteId: note.id,
            studentNoteGroupId: note.studentNoteGroupId.toString(),
            title: note.title,
            body: note.body,
        });

        latestSavedNote.value = note;
        lastSavedFingerprint.value = fingerprint(snapshot());
        status.value = 'saved';
    }

    async function performSave(targetSessionId: number): Promise<boolean> {
        if (targetSessionId !== sessionId) {
            return true;
        }

        const current = snapshot();
        const currentFingerprint = fingerprint(current);

        if (currentFingerprint === lastSavedFingerprint.value) {
            status.value = draft.persistedNoteId === null ? 'idle' : 'saved';

            return true;
        }

        if (draft.persistedNoteId === null && !hasMeaningfulContent(current)) {
            status.value = 'idle';

            return true;
        }

        request.student_note_group_id = current.studentNoteGroupId;
        request.title = current.title;
        request.body = current.body;
        request.clearErrors();
        status.value = 'saving';

        try {
            const action =
                draft.persistedNoteId === null
                    ? storeNote(toValue(currentTeamSlug))
                    : updateNote([
                          toValue(currentTeamSlug),
                          draft.persistedNoteId,
                      ]);
            const response = await request.submit(action);

            if (targetSessionId !== sessionId) {
                return true;
            }

            if (!response?.note) {
                status.value = 'error';

                return false;
            }

            const savedSnapshot = snapshotFromNote(response.note);
            const draftWasUnchanged =
                fingerprint(snapshot()) === currentFingerprint;

            draft.persistedNoteId = response.note.id;
            latestSavedNote.value = response.note;
            lastSavedFingerprint.value = fingerprint(savedSnapshot);

            if (draftWasUnchanged) {
                Object.assign(draft, {
                    studentNoteGroupId:
                        response.note.studentNoteGroupId.toString(),
                    title: response.note.title,
                    body: response.note.body,
                });
                status.value = 'saved';
            } else {
                status.value = 'pending';
            }

            return true;
        } catch {
            if (targetSessionId === sessionId) {
                status.value = 'error';
            }

            return false;
        }
    }

    function enqueueSave(): Promise<boolean> {
        const targetSessionId = sessionId;
        const nextSave = saveQueue.then(
            () => performSave(targetSessionId),
            () => performSave(targetSessionId),
        );

        saveQueue = nextSave;

        return nextSave;
    }

    function scheduleAutosave(): void {
        clearAutosaveTimer();
        request.clearErrors();

        if (!hasUnsavedChanges.value) {
            status.value = draft.persistedNoteId === null ? 'idle' : 'saved';

            return;
        }

        if (status.value !== 'saving') {
            status.value = 'pending';
        }

        autosaveTimer = setTimeout(() => {
            autosaveTimer = null;
            void enqueueSave();
        }, AUTOSAVE_DELAY);
    }

    async function flush(): Promise<boolean> {
        clearAutosaveTimer();

        while (hasUnsavedChanges.value) {
            const saved = await enqueueSave();

            if (!saved) {
                return false;
            }
        }

        return true;
    }

    async function retry(): Promise<boolean> {
        clearAutosaveTimer();

        return flush();
    }

    function discardPendingChanges(): void {
        sessionId++;
        clearAutosaveTimer();
        request.cancel();
        request.resetAndClearErrors();
        lastSavedFingerprint.value = fingerprint(snapshot());
        status.value = draft.persistedNoteId === null ? 'idle' : 'saved';
    }

    watch(
        () => [draft.studentNoteGroupId, draft.title, draft.body],
        scheduleAutosave,
        { flush: 'post' },
    );

    onBeforeUnmount(() => {
        clearAutosaveTimer();
    });

    return {
        draft,
        errors,
        hasUnsavedChanges,
        isSaving,
        latestSavedNote,
        status,
        discardPendingChanges,
        flush,
        retry,
        startCreate,
        startEdit,
    };
}
