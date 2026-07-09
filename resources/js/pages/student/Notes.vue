<script setup lang="ts">
import type { PendingVisit } from '@inertiajs/core';
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    ChevronDown,
    FileText,
    FolderPlus,
    LoaderCircle,
    Pencil,
    Plus,
    Redo2,
    SquarePen,
    StickyNote,
    Trash2,
    Undo2,
    X,
} from '@lucide/vue';
import type { CSSProperties } from 'vue';
import {
    computed,
    defineAsyncComponent,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useStudentNoteAutosave } from '@/composables/useStudentNoteAutosave';
import {
    store as storeGroup,
    update as updateGroup,
} from '@/routes/note-groups';
import { destroy as destroyNote, index as notesIndex } from '@/routes/notes';
import type { StudentNote, StudentNoteGroup } from '@/types';

type Props = {
    groups: StudentNoteGroup[];
    notes: StudentNote[];
    selectedGroupId: number | null;
    selectedNoteId: number | null;
};

type MarkdownEditorApi = {
    focus: () => void;
    getMarkdown: () => string;
    redo: () => void;
    undo: () => void;
};

const groupAccentPalette = [
    {
        accent: '#078c96',
        badge: '#d7eef0',
        ink: '#08747c',
        tint: '#e5f3f5',
    },
    {
        accent: '#ff6a54',
        badge: '#ffe1da',
        ink: '#b84d3c',
        tint: '#fff0ed',
    },
    {
        accent: '#f2b521',
        badge: '#ffefc3',
        ink: '#856100',
        tint: '#fff8e5',
    },
] as const;

const props = defineProps<Props>();
const page = usePage();
const MarkdownNoteEditor = defineAsyncComponent(
    () => import('@/components/MarkdownNoteEditor.vue'),
);

defineOptions({
    layout: () => ({
        breadcrumbs: [],
        hideDesktopHeader: true,
    }),
});

const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const activeGroup = computed(
    () =>
        props.groups.find((group) => group.id === props.selectedGroupId) ??
        props.groups[0] ??
        null,
);
const initiallySelectedNote = computed(() =>
    props.selectedNoteId === null
        ? null
        : (props.notes.find((note) => note.id === props.selectedNoteId) ??
          null),
);

const {
    draft,
    errors: autosaveErrors,
    hasUnsavedChanges,
    isSaving,
    status: autosaveStatus,
    discardPendingChanges,
    flush,
    retry,
    startCreate,
    startEdit,
} = useStudentNoteAutosave(currentTeamSlug);

const editingGroupId = ref<number | null>(null);
const isGroupFormOpen = ref(props.groups.length === 0);
const isMobileGroupsOpen = ref(props.groups.length === 0);
const isEditorOpen = ref(false);
const editorAutofocusBody = ref(false);
const editorReady = ref(false);
const editorSessionKey = ref(0);
const pendingDeleteNoteId = ref<number | null>(null);
const pendingAfterSaveAction = ref<(() => void) | null>(null);
const suppressNavigationGuard = ref(false);
const editGroupFormKey = ref(0);
const pageRootRef = ref<HTMLElement | null>(null);
const noteEditorRef = ref<HTMLElement | null>(null);
const markdownEditorRef = ref<MarkdownEditorApi | null>(null);
let removeInertiaBeforeListener: (() => void) | undefined;

const noteDateFormatter = new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function groupAccentStyle(index: number): CSSProperties {
    const palette =
        groupAccentPalette[index % groupAccentPalette.length] ??
        groupAccentPalette[0];

    return {
        '--notes-group-accent': palette.accent,
        '--notes-group-badge': palette.badge,
        '--notes-group-ink': palette.ink,
        '--notes-group-tint': palette.tint,
    };
}

const selectedDraftGroupName = computed(
    () =>
        props.groups.find(
            (group) => group.id === Number(draft.studentNoteGroupId),
        )?.name ?? 'No group selected',
);

const displayedActiveGroupId = computed(() =>
    isEditorOpen.value
        ? Number(draft.studentNoteGroupId)
        : activeGroup.value?.id,
);

const displayedMobileGroupName = computed(() =>
    isEditorOpen.value
        ? selectedDraftGroupName.value
        : (activeGroup.value?.name ?? 'No group selected'),
);

const autosaveLabel = computed(() => {
    if (
        autosaveStatus.value === 'pending' ||
        autosaveStatus.value === 'saving'
    ) {
        return 'Saving...';
    }

    if (autosaveStatus.value === 'error') {
        return 'Autosave failed';
    }

    if (autosaveStatus.value === 'saved' || draft.persistedNoteId !== null) {
        return 'Saved';
    }

    return 'Start writing to create this note';
});

function groupHref(group: StudentNoteGroup) {
    return notesIndex(currentTeamSlug.value, {
        query: { group: group.id },
    });
}

function noteCountLabel(count: number): string {
    return `${count} ${count === 1 ? 'note' : 'notes'} in this group`;
}

function formatNoteUpdatedAt(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return `Updated ${noteDateFormatter.format(date)}`;
}

function firstError(error: unknown): string | undefined {
    if (typeof error === 'string') {
        return error;
    }

    if (Array.isArray(error) && typeof error[0] === 'string') {
        return error[0];
    }

    return undefined;
}

async function focusPageElement(selector: string): Promise<void> {
    await nextTick();
    pageRootRef.value?.querySelector<HTMLElement>(selector)?.focus();
}

async function scrollToEditor(): Promise<void> {
    await nextTick();

    noteEditorRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

async function openGroupForm(): Promise<void> {
    if (isEditorOpen.value) {
        return;
    }

    isGroupFormOpen.value = true;
    isMobileGroupsOpen.value = true;

    await focusPageElement('[data-test="group-name-input"]');
}

function toggleGroupForm(): void {
    if (isEditorOpen.value) {
        return;
    }

    if (isGroupFormOpen.value) {
        isGroupFormOpen.value = false;

        return;
    }

    void openGroupForm();
}

async function handleGroupCreated(): Promise<void> {
    isGroupFormOpen.value = false;
    isMobileGroupsOpen.value = false;

    if (props.selectedGroupId !== null) {
        await focusPageElement(`[data-group-link="${props.selectedGroupId}"]`);
    }
}

async function startEditingGroup(group: StudentNoteGroup): Promise<void> {
    if (isEditorOpen.value) {
        return;
    }

    editingGroupId.value = group.id;
    isMobileGroupsOpen.value = true;
    editGroupFormKey.value++;

    await nextTick();

    const input = pageRootRef.value?.querySelector<HTMLInputElement>(
        `#edit-group-name-${group.id}`,
    );

    input?.focus();
    input?.select();
}

async function stopEditingGroup(restoreFocus = true): Promise<void> {
    const groupId = editingGroupId.value;
    editingGroupId.value = null;
    editGroupFormKey.value++;

    if (restoreFocus && groupId !== null) {
        await focusPageElement(`[data-edit-group-id="${groupId}"]`);
    }
}

async function openCreateNote(): Promise<void> {
    if (!activeGroup.value) {
        return;
    }

    isGroupFormOpen.value = false;
    editingGroupId.value = null;
    pendingDeleteNoteId.value = null;
    pendingAfterSaveAction.value = null;
    editorAutofocusBody.value = false;
    editorReady.value = false;
    editorSessionKey.value++;
    startCreate(activeGroup.value.id);
    isEditorOpen.value = true;

    await scrollToEditor();
    await focusPageElement('[data-test="note-title-input"]');
}

async function openNote(note: StudentNote): Promise<void> {
    isGroupFormOpen.value = false;
    editingGroupId.value = null;
    pendingDeleteNoteId.value = null;
    pendingAfterSaveAction.value = null;
    editorAutofocusBody.value = true;
    editorReady.value = false;
    editorSessionKey.value++;
    startEdit(note);
    isEditorOpen.value = true;

    await scrollToEditor();
}

async function continueAfterAutosave(action: () => void): Promise<void> {
    syncEditorDraft();
    pendingAfterSaveAction.value = action;
    const saved = await flush();

    if (saved && pendingAfterSaveAction.value === action) {
        pendingAfterSaveAction.value = null;
        action();
    }
}

function syncEditorDraft(): void {
    if (!isEditorOpen.value || !editorReady.value) {
        return;
    }

    draft.body = markdownEditorRef.value?.getMarkdown() ?? draft.body;
}

async function retryAutosave(): Promise<void> {
    const saved = await retry();

    if (!saved) {
        return;
    }

    const action = pendingAfterSaveAction.value;
    pendingAfterSaveAction.value = null;
    action?.();
}

function discardChangesAndContinue(): void {
    const action = pendingAfterSaveAction.value;

    discardPendingChanges();
    pendingAfterSaveAction.value = null;
    action?.();
}

function closeEditorAndRefresh(): void {
    const noteId = draft.persistedNoteId;
    const groupId = Number(draft.studentNoteGroupId);

    isEditorOpen.value = false;
    editorReady.value = false;
    pendingDeleteNoteId.value = null;

    if (noteId === null) {
        void focusPageElement('[data-test="create-note-button"]');

        return;
    }

    router.visit(
        notesIndex(currentTeamSlug.value, {
            query: { group: groupId },
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                void focusPageElement(`[data-note-editor-id="${noteId}"]`);
            },
        },
    );
}

function requestCloseEditor(): void {
    void continueAfterAutosave(closeEditorAndRefresh);
}

function handlePrimaryAction(): void {
    if (props.groups.length === 0) {
        void openGroupForm();

        return;
    }

    if (!isEditorOpen.value) {
        void openCreateNote();

        return;
    }

    void continueAfterAutosave(() => {
        void openCreateNote();
    });
}

function handleGroupNavigation(
    event: MouseEvent,
    group: StudentNoteGroup,
): void {
    isMobileGroupsOpen.value = false;

    if (!isEditorOpen.value) {
        return;
    }

    event.preventDefault();

    void continueAfterAutosave(() => {
        router.visit(groupHref(group));
    });
}

function requestDeleteNote(note: StudentNote): void {
    pendingDeleteNoteId.value = note.id;
}

function cancelDeleteNote(): void {
    pendingDeleteNoteId.value = null;
}

function confirmDeleteNote(note: StudentNote): void {
    pendingDeleteNoteId.value = null;

    router.visit(destroyNote([currentTeamSlug.value, note.id]), {
        preserveScroll: true,
    });
}

function replayVisit(visit: PendingVisit): void {
    suppressNavigationGuard.value = true;

    router.visit(visit.url, {
        method: visit.method,
        data: visit.data,
        replace: visit.replace,
        preserveScroll: visit.preserveScroll,
        preserveState: visit.preserveState,
        only: visit.only,
        except: visit.except,
        headers: visit.headers,
        forceFormData: visit.forceFormData,
        queryStringArrayFormat: visit.queryStringArrayFormat,
        showProgress: visit.showProgress,
        onFinish: () => {
            suppressNavigationGuard.value = false;
        },
    });
}

function handleBeforeUnload(event: BeforeUnloadEvent): void {
    syncEditorDraft();

    if (!hasUnsavedChanges.value) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

onMounted(() => {
    removeInertiaBeforeListener = router.on('before', (event) => {
        syncEditorDraft();

        if (
            suppressNavigationGuard.value ||
            !isEditorOpen.value ||
            !hasUnsavedChanges.value
        ) {
            return;
        }

        const visit = event.detail.visit;

        event.preventDefault();
        void continueAfterAutosave(() => replayVisit(visit));

        return false;
    });

    window.addEventListener('beforeunload', handleBeforeUnload);

    if (initiallySelectedNote.value !== null) {
        void openNote(initiallySelectedNote.value);
    }
});

onBeforeUnmount(() => {
    removeInertiaBeforeListener?.();
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <div ref="pageRootRef" class="contents">
        <Head title="Notes" />

        <div
            class="notes-page flex min-h-[calc(100svh-4rem)] flex-1 flex-col bg-[#f7fafb] md:min-h-svh dark:bg-[#101617]"
        >
            <div
                class="flex min-h-28 shrink-0 flex-col justify-center gap-5 border-b border-[#dfe7eb] bg-[#f8fbfc] px-5 py-6 sm:flex-row sm:items-center sm:justify-between md:px-9 lg:min-h-[7.5rem] dark:border-[#263335] dark:bg-[#11191a]"
            >
                <div class="space-y-1.5">
                    <h1
                        class="text-3xl leading-tight font-semibold text-[#17212b] dark:text-[#f1f6f6]"
                    >
                        Notes
                    </h1>
                    <p class="text-sm text-[#697789] sm:text-base">
                        Capture private study notes and keep them grouped by
                        topic.
                    </p>
                </div>

                <Button
                    data-test="create-note-button"
                    class="h-12 min-w-[8.75rem] self-start bg-[#ff654f] px-6 text-white shadow-[0_8px_18px_-12px_rgba(255,101,79,0.9)] hover:bg-[#f45641] focus-visible:ring-[#ff654f]/35 sm:self-auto dark:bg-[#ff705d] dark:hover:bg-[#ff806f]"
                    @click="handlePrimaryAction"
                >
                    <FolderPlus v-if="groups.length === 0" />
                    <SquarePen v-else />
                    {{ groups.length === 0 ? 'Create a group' : 'New note' }}
                </Button>
            </div>

            <div
                class="grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)] lg:grid-cols-[16.5rem_minmax(0,1fr)] lg:grid-rows-1"
            >
                <section
                    class="min-w-0 border-b border-[#dfe7eb] bg-[#fbfdfe] lg:min-h-[calc(100svh-7.5rem)] lg:border-r lg:border-b-0 dark:border-[#263335] dark:bg-[#121a1b]"
                >
                    <h2 class="sr-only">Groups</h2>

                    <div class="flex items-stretch gap-2 p-4 lg:hidden">
                        <button
                            type="button"
                            class="flex min-h-11 min-w-0 flex-1 items-center justify-between gap-3 rounded-md border border-[#dbe5e9] bg-white px-3 py-2 text-left transition-colors hover:bg-[#eff7f8] focus-visible:border-[#078c96] focus-visible:ring-[3px] focus-visible:ring-[#078c96]/20 focus-visible:outline-none dark:border-[#2b3b3d] dark:bg-[#172122] dark:hover:bg-[#1c2b2d]"
                            aria-controls="notes-groups-panel"
                            :aria-expanded="isMobileGroupsOpen"
                            @click="isMobileGroupsOpen = !isMobileGroupsOpen"
                        >
                            <span class="min-w-0">
                                <span
                                    class="flex items-center gap-2 text-sm font-semibold text-[#23303c] dark:text-[#e5eded]"
                                >
                                    Groups
                                    <Badge
                                        class="bg-[#e2f1f3] text-[#087b85]"
                                        variant="secondary"
                                    >
                                        {{ groups.length }}
                                    </Badge>
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ displayedMobileGroupName }}
                                </span>
                            </span>
                            <ChevronDown
                                class="size-4 shrink-0 transition-transform"
                                :class="isMobileGroupsOpen ? 'rotate-180' : ''"
                            />
                        </button>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-lg"
                                    class="border-[#dbe5e9] bg-white text-[#087b85] hover:bg-[#e7f3f4] hover:text-[#087b85] dark:border-[#2b3b3d] dark:bg-[#172122]"
                                    :disabled="isEditorOpen"
                                    :aria-label="
                                        isGroupFormOpen
                                            ? 'Close new group form'
                                            : 'Create a new group'
                                    "
                                    @click="toggleGroupForm"
                                >
                                    <X v-if="isGroupFormOpen" />
                                    <FolderPlus v-else />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{
                                    isEditorOpen
                                        ? 'Close the note before creating a group'
                                        : isGroupFormOpen
                                          ? 'Close new group form'
                                          : 'New group'
                                }}
                            </TooltipContent>
                        </Tooltip>
                    </div>

                    <div
                        class="hidden min-h-[4.75rem] items-center border-b border-[#dfe7eb] px-6 lg:flex dark:border-[#263335]"
                    >
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="justify-start px-0 text-[#087f88] hover:bg-transparent hover:text-[#066b73] disabled:opacity-75 dark:text-[#64cbd1] dark:hover:text-[#8edce0]"
                                    :disabled="isEditorOpen"
                                    :aria-label="
                                        isGroupFormOpen
                                            ? 'Close new group form'
                                            : 'Create a new group'
                                    "
                                    @click="toggleGroupForm"
                                >
                                    <X v-if="isGroupFormOpen" />
                                    <Plus v-else />
                                    {{
                                        isGroupFormOpen
                                            ? 'Close form'
                                            : 'New group'
                                    }}
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{
                                    isEditorOpen
                                        ? 'Close the note before creating a group'
                                        : isGroupFormOpen
                                          ? 'Close new group form'
                                          : 'New group'
                                }}
                            </TooltipContent>
                        </Tooltip>
                    </div>

                    <div
                        id="notes-groups-panel"
                        :class="
                            isMobileGroupsOpen ? 'block' : 'hidden lg:block'
                        "
                    >
                        <Form
                            v-if="isGroupFormOpen && !isEditorOpen"
                            v-bind="storeGroup.form(currentTeamSlug)"
                            reset-on-success
                            class="space-y-3 border-b border-[#dfe7eb] bg-white/70 p-4 dark:border-[#263335] dark:bg-[#172122]"
                            v-slot="{ errors, processing }"
                            @success="handleGroupCreated"
                        >
                            <div class="grid gap-2">
                                <Label for="group-name">New group</Label>
                                <Input
                                    id="group-name"
                                    name="name"
                                    data-test="group-name-input"
                                    placeholder="Laravel Basics"
                                    class="border-[#d6e1e5] bg-white dark:border-[#304244] dark:bg-[#11191a]"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <Button
                                type="submit"
                                size="sm"
                                class="w-full bg-[#078c96] text-white hover:bg-[#067983]"
                                :disabled="processing"
                            >
                                <FolderPlus />
                                {{
                                    processing ? 'Creating...' : 'Create group'
                                }}
                            </Button>
                        </Form>

                        <div
                            v-if="groups.length > 0"
                            class="space-y-1 p-3 lg:max-h-[calc(100svh-12.25rem)] lg:overflow-y-auto"
                        >
                            <div
                                v-for="(group, groupIndex) in groups"
                                :key="group.id"
                                class="group/note-group relative min-w-0"
                                :style="groupAccentStyle(groupIndex)"
                            >
                                <Form
                                    v-if="
                                        editingGroupId === group.id &&
                                        !isEditorOpen
                                    "
                                    :key="`edit-group-${group.id}-${editGroupFormKey}`"
                                    v-bind="
                                        updateGroup.form([
                                            currentTeamSlug,
                                            group.id,
                                        ])
                                    "
                                    class="space-y-3 rounded-md border border-[var(--notes-group-accent)] bg-white p-3 dark:bg-[#172122]"
                                    v-slot="{ errors, processing, isDirty }"
                                    @success="stopEditingGroup()"
                                >
                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-group-name-${group.id}`"
                                        >
                                            Rename group
                                        </Label>
                                        <Input
                                            :id="`edit-group-name-${group.id}`"
                                            name="name"
                                            data-test="edit-group-name-input"
                                            :default-value="group.name"
                                            class="bg-white dark:bg-[#11191a]"
                                            required
                                            @keydown.esc.prevent="
                                                stopEditingGroup()
                                            "
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="flex gap-2">
                                        <Button
                                            type="submit"
                                            size="sm"
                                            class="bg-[#078c96] text-white hover:bg-[#067983]"
                                            :disabled="processing || !isDirty"
                                        >
                                            {{
                                                processing
                                                    ? 'Saving...'
                                                    : 'Save'
                                            }}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            @click="stopEditingGroup()"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </Form>

                                <div v-else class="relative min-w-0">
                                    <Link
                                        :href="groupHref(group)"
                                        :data-group-link="group.id"
                                        class="relative flex h-12 min-w-0 items-center gap-3 overflow-hidden rounded-md pr-20 pl-6 text-left text-sm transition-colors hover:bg-[#f0f6f7] focus-visible:ring-[3px] focus-visible:ring-[#078c96]/20 focus-visible:outline-none dark:hover:bg-[#1a292b]"
                                        :class="
                                            group.id === displayedActiveGroupId
                                                ? 'bg-[var(--notes-group-tint)] font-semibold text-[#17212b] dark:!bg-[#183033] dark:text-[#edf5f5]'
                                                : 'text-[#2c3844] dark:text-[#cbd6d7]'
                                        "
                                        @click="
                                            handleGroupNavigation($event, group)
                                        "
                                    >
                                        <span
                                            class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-[var(--notes-group-accent)]"
                                            aria-hidden="true"
                                        />
                                        <span class="min-w-0 flex-1 truncate">
                                            {{ group.name }}
                                        </span>
                                        <span
                                            class="absolute right-3 inline-flex min-w-7 items-center justify-center rounded-md px-2 py-0.5 text-xs font-semibold transition-opacity lg:group-focus-within/note-group:opacity-0 lg:group-hover/note-group:opacity-0 dark:!bg-white/10 dark:!text-[#d8e7e8]"
                                            :style="{
                                                backgroundColor:
                                                    'var(--notes-group-badge)',
                                                color: 'var(--notes-group-ink)',
                                            }"
                                        >
                                            {{ group.notesCount }}
                                        </span>
                                    </Link>

                                    <Tooltip v-if="!isEditorOpen">
                                        <TooltipTrigger as-child>
                                            <Button
                                                data-test="edit-group-button"
                                                :data-edit-group-id="group.id"
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                class="absolute top-1/2 right-2 z-10 -translate-y-1/2 bg-white/85 text-[#667584] opacity-100 shadow-sm hover:bg-white hover:text-[#087b85] lg:opacity-0 lg:shadow-none lg:group-focus-within/note-group:opacity-100 lg:group-hover/note-group:opacity-100 dark:bg-[#213033] dark:text-[#b8c7c9]"
                                                :disabled="isEditorOpen"
                                                :aria-label="`Rename ${group.name}`"
                                                @click="
                                                    startEditingGroup(group)
                                                "
                                            >
                                                <Pencil />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{
                                                isEditorOpen
                                                    ? 'Close the note before renaming a group'
                                                    : 'Rename group'
                                            }}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="!isGroupFormOpen"
                            class="space-y-3 border-b border-dashed border-[#d6e1e5] p-5 text-sm text-[#697789] dark:border-[#304244]"
                        >
                            <p>Create the first group before adding notes.</p>
                            <Button
                                size="sm"
                                class="bg-[#078c96] text-white hover:bg-[#067983]"
                                @click="openGroupForm"
                            >
                                <Plus /> Create group
                            </Button>
                        </div>
                    </div>
                </section>

                <section
                    v-if="!isEditorOpen"
                    class="min-w-0 bg-white px-5 py-7 md:px-8 lg:px-10 lg:py-9 dark:bg-[#0e1415]"
                >
                    <div class="mb-7 flex items-end justify-between gap-4">
                        <div class="space-y-1">
                            <h2
                                class="text-2xl font-semibold text-[#17212b] dark:text-[#eff5f5]"
                            >
                                {{ activeGroup?.name ?? 'No group selected' }}
                            </h2>
                            <p class="text-sm text-[#718091]">
                                {{ noteCountLabel(notes.length) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="groups.length === 0"
                        class="flex min-h-72 flex-col items-center justify-center gap-4 rounded-md border border-dashed border-[#ccdadd] bg-[#f8fbfc] p-8 text-center dark:border-[#304244] dark:bg-[#121a1b]"
                    >
                        <StickyNote class="size-10 text-[#78aeb3]" />
                        <div class="space-y-1">
                            <p class="font-medium">No note groups yet</p>
                            <p class="text-sm text-[#718091]">
                                Start by creating a group for a topic or lesson.
                            </p>
                        </div>
                        <Button
                            class="bg-[#078c96] text-white hover:bg-[#067983]"
                            @click="openGroupForm"
                        >
                            <Plus /> Create group
                        </Button>
                    </div>

                    <div
                        v-else-if="notes.length === 0"
                        class="flex min-h-72 flex-col items-center justify-center gap-4 rounded-md border border-dashed border-[#ccdadd] bg-[#f8fbfc] p-8 text-center dark:border-[#304244] dark:bg-[#121a1b]"
                    >
                        <FileText class="size-10 text-[#78aeb3]" />
                        <div class="space-y-1">
                            <p class="font-medium">No notes in this group</p>
                            <p class="text-sm text-[#718091]">
                                Add the first note to keep this topic organized.
                            </p>
                        </div>
                        <Button
                            class="bg-[#ff654f] text-white hover:bg-[#f45641]"
                            @click="openCreateNote"
                        >
                            <SquarePen /> New note
                        </Button>
                    </div>

                    <div
                        v-else
                        class="border-t border-[#dfe7eb] dark:border-[#263335]"
                    >
                        <article
                            v-for="note in notes"
                            :key="note.id"
                            data-test="student-note-row"
                            class="group/note border-b border-[#dfe7eb] px-3 py-5 transition-colors focus-within:bg-[#f2f8f9] hover:bg-[#f5fafb] dark:border-[#263335] dark:focus-within:bg-[#142123] dark:hover:bg-[#121d1f]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <button
                                    type="button"
                                    :data-note-editor-id="note.id"
                                    class="min-w-0 flex-1 space-y-2 text-left focus-visible:outline-none"
                                    :aria-label="`Open ${note.title}`"
                                    @click="openNote(note)"
                                >
                                    <h3
                                        class="text-[17px] font-semibold break-words text-[#1c2833] dark:text-[#ecf3f3]"
                                    >
                                        {{ note.title }}
                                    </h3>
                                    <p
                                        class="line-clamp-3 max-w-[72ch] text-sm leading-6 break-words text-[#657485]"
                                    >
                                        {{ note.bodyPreview }}
                                    </p>
                                    <p
                                        v-if="note.updatedAt"
                                        class="text-xs text-[#8a97a5]"
                                    >
                                        {{
                                            formatNoteUpdatedAt(note.updatedAt)
                                        }}
                                    </p>
                                </button>

                                <Tooltip v-if="pendingDeleteNoteId !== note.id">
                                    <TooltipTrigger as-child>
                                        <Button
                                            data-test="delete-note-button"
                                            type="button"
                                            variant="ghost"
                                            size="icon-lg"
                                            class="shrink-0 text-destructive opacity-100 hover:bg-red-50 hover:text-destructive lg:opacity-0 lg:group-focus-within/note:opacity-100 lg:group-hover/note:opacity-100 dark:hover:bg-red-950/30"
                                            :aria-label="`Delete ${note.title}`"
                                            @click.stop="
                                                requestDeleteNote(note)
                                            "
                                        >
                                            <Trash2 />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Delete note</TooltipContent>
                                </Tooltip>
                            </div>

                            <div
                                v-if="pendingDeleteNoteId === note.id"
                                class="mt-4 flex flex-col gap-3 border-t border-[#e4eaed] pt-3 sm:flex-row sm:items-center sm:justify-between dark:border-[#2b393b]"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Delete this note? This cannot be undone.
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="confirmDeleteNote(note)"
                                    >
                                        <Trash2 /> Delete
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        @click="cancelDeleteNote"
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section
                    v-else
                    ref="noteEditorRef"
                    class="min-w-0 bg-white px-5 py-5 md:px-8 lg:px-10 lg:py-6 dark:bg-[#0e1415]"
                    data-test="note-editor-workspace"
                >
                    <h2 class="sr-only">
                        {{ draft.persistedNoteId ? 'Note editor' : 'New note' }}
                    </h2>

                    <div
                        class="flex flex-col gap-4 border-b border-[#dfe7eb] pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-[#263335]"
                    >
                        <div
                            class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <Label for="note-group" class="sr-only">
                                Group
                            </Label>
                            <Select
                                id="note-group"
                                v-model="draft.studentNoteGroupId"
                                data-test="note-group-select"
                            >
                                <SelectTrigger
                                    class="h-11 w-full border-[#d6e1e5] bg-white sm:w-44 dark:border-[#304244] dark:bg-[#151e20]"
                                >
                                    <SelectValue placeholder="Select a group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="group in groups"
                                        :key="group.id"
                                        :value="group.id.toString()"
                                    >
                                        {{ group.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                class="flex min-h-8 items-center gap-2 text-sm font-medium"
                                :class="
                                    autosaveStatus === 'error'
                                        ? 'text-destructive'
                                        : 'text-[#087f88] dark:text-[#67cbd1]'
                                "
                                role="status"
                                aria-live="polite"
                            >
                                <LoaderCircle
                                    v-if="isSaving"
                                    class="size-4 animate-spin"
                                />
                                <AlertCircle
                                    v-else-if="autosaveStatus === 'error'"
                                    class="size-4"
                                />
                                <Check
                                    v-else-if="autosaveStatus === 'saved'"
                                    class="size-4"
                                />
                                <span>{{ autosaveLabel }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-lg"
                                        class="h-11 w-14 border-[#d6e1e5] bg-white text-[#344250] hover:bg-[#f0f6f7] hover:text-[#087b85] dark:border-[#304244] dark:bg-[#151e20] dark:text-[#d5dfe0]"
                                        :disabled="!editorReady"
                                        aria-label="Undo"
                                        @click="markdownEditorRef?.undo()"
                                    >
                                        <Undo2 />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Undo</TooltipContent>
                            </Tooltip>

                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-lg"
                                        class="h-11 w-14 border-[#d6e1e5] bg-white text-[#344250] hover:bg-[#f0f6f7] hover:text-[#087b85] dark:border-[#304244] dark:bg-[#151e20] dark:text-[#d5dfe0]"
                                        :disabled="!editorReady"
                                        aria-label="Redo"
                                        @click="markdownEditorRef?.redo()"
                                    >
                                        <Redo2 />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Redo</TooltipContent>
                            </Tooltip>

                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-lg"
                                        class="h-11 w-14 border-[#d6e1e5] bg-white text-[#344250] hover:bg-[#f0f6f7] hover:text-[#087b85] dark:border-[#304244] dark:bg-[#151e20] dark:text-[#d5dfe0]"
                                        aria-label="Close note editor"
                                        @click="requestCloseEditor"
                                    >
                                        <X />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Close editor</TooltipContent>
                            </Tooltip>
                        </div>
                    </div>

                    <InputError
                        class="mt-3"
                        :message="
                            firstError(autosaveErrors.student_note_group_id)
                        "
                    />

                    <div class="max-w-[86rem]">
                        <div
                            class="grid gap-2 border-b border-[#dfe7eb] pt-5 pb-5 dark:border-[#263335]"
                        >
                            <Label for="note-title" class="sr-only">
                                Title
                            </Label>
                            <Input
                                id="note-title"
                                v-model="draft.title"
                                data-test="note-title-input"
                                maxlength="255"
                                placeholder="Untitled note"
                                class="h-auto rounded-none border-0 bg-transparent px-0 py-2 text-3xl leading-tight font-semibold text-[#17212b] shadow-none placeholder:text-[#9ba6b1] focus-visible:border-transparent focus-visible:ring-0 md:text-4xl dark:bg-transparent dark:text-[#eff5f5]"
                            />
                            <InputError
                                :message="firstError(autosaveErrors.title)"
                            />
                        </div>

                        <div class="grid max-w-[52rem] gap-2 pt-5">
                            <Label class="sr-only">Note body</Label>
                            <Suspense>
                                <MarkdownNoteEditor
                                    :key="editorSessionKey"
                                    ref="markdownEditorRef"
                                    v-model="draft.body"
                                    :autofocus="editorAutofocusBody"
                                    @ready="editorReady = true"
                                />
                                <template #fallback>
                                    <div
                                        class="space-y-4 rounded-md border border-[#d6e1e5] p-5 dark:border-[#304244]"
                                    >
                                        <Skeleton class="h-10 w-full" />
                                        <Skeleton class="h-72 w-full" />
                                    </div>
                                </template>
                            </Suspense>
                            <InputError
                                :message="firstError(autosaveErrors.body)"
                            />
                        </div>
                    </div>

                    <div
                        v-if="autosaveStatus === 'error'"
                        class="sticky bottom-3 z-20 mt-4 flex flex-col gap-3 rounded-md border border-destructive/40 bg-white/95 p-3 shadow-sm backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:bg-[#151e20]/95"
                        role="alert"
                    >
                        <div class="flex items-start gap-2">
                            <AlertCircle
                                class="mt-0.5 size-4 shrink-0 text-destructive"
                            />
                            <div>
                                <p class="text-sm font-medium">
                                    Your latest changes could not be saved.
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Your text is still here. Retry when the
                                    connection is available.
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                @click="retryAutosave"
                            >
                                Retry
                            </Button>
                            <Button
                                v-if="pendingAfterSaveAction"
                                type="button"
                                size="sm"
                                variant="secondary"
                                @click="discardChangesAndContinue"
                            >
                                Discard changes
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
