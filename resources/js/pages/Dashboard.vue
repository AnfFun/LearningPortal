<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, FileText, StickyNote } from '@lucide/vue';
import { computed } from 'vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';
import { index as notesIndex } from '@/routes/notes';
import type { DashboardInvitation, DashboardRecentNote, Team } from '@/types';

const props = withDefaults(
    defineProps<{
        pendingInvitations?: DashboardInvitation[];
        recentNotes?: DashboardRecentNote[];
    }>(),
    {
        pendingInvitations: () => [],
        recentNotes: () => [],
    },
);

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const notesHref = computed(() =>
    currentTeamSlug.value ? notesIndex(currentTeamSlug.value) : '/',
);
const noteDateFormatter = new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
});

function recentNoteHref(note: DashboardRecentNote) {
    if (!currentTeamSlug.value) {
        return '/';
    }

    return notesIndex(currentTeamSlug.value, {
        query: {
            group: note.studentNoteGroupId,
            note: note.id,
        },
    });
}

function formatNoteDate(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '' : noteDateFormatter.format(date);
}

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="props.pendingInvitations.length > 0"
        :invitations="props.pendingInvitations"
    />

    <div
        class="flex h-full min-w-0 flex-1 flex-col gap-5 overflow-x-hidden p-4 md:p-5"
    >
        <div
            class="grid min-w-0 gap-5 lg:grid-cols-[minmax(18rem,1fr)_minmax(0,2fr)]"
        >
            <section
                data-test="recent-notes-widget"
                class="flex min-h-80 min-w-0 flex-col overflow-hidden rounded-lg border border-[#dfe7eb] bg-white dark:border-[#263335] dark:bg-[#121a1b]"
            >
                <div
                    class="flex min-h-16 items-center justify-between gap-4 border-b border-[#e5ecef] px-5 py-4 dark:border-[#263335]"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-[#e4f3f4] text-[#087b85] dark:bg-[#123b3e] dark:text-[#6ed3d8]"
                        >
                            <StickyNote class="size-[18px]" />
                        </span>
                        <h2
                            class="truncate text-base font-semibold text-[#17212b] dark:text-[#f1f6f6]"
                        >
                            Recent notes
                        </h2>
                    </div>

                    <Link
                        :href="notesHref"
                        class="flex shrink-0 items-center gap-1.5 text-sm font-medium text-[#087b85] outline-hidden hover:text-[#056670] focus-visible:ring-2 focus-visible:ring-[#078c96]/35 dark:text-[#6ed3d8] dark:hover:text-[#8ce4e8]"
                    >
                        View all
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div
                    v-if="props.recentNotes.length > 0"
                    class="divide-y divide-[#e9eff1] dark:divide-[#263335]"
                >
                    <Link
                        v-for="note in props.recentNotes"
                        :key="note.id"
                        data-test="recent-note-link"
                        :href="recentNoteHref(note)"
                        prefetch
                        class="group block min-w-0 px-5 py-3.5 outline-hidden transition-colors hover:bg-[#f3f8f9] focus-visible:bg-[#f3f8f9] focus-visible:ring-2 focus-visible:ring-[#078c96]/35 focus-visible:ring-inset dark:hover:bg-[#182426] dark:focus-visible:bg-[#182426]"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <h3
                                    class="truncate text-sm font-semibold text-[#17212b] dark:text-[#f1f6f6]"
                                >
                                    {{ note.title }}
                                </h3>
                                <p
                                    class="mt-1 truncate text-sm text-[#697789] dark:text-[#9ba9ad]"
                                >
                                    {{ note.bodyPreview || 'Empty note' }}
                                </p>
                            </div>

                            <time
                                v-if="note.updatedAt"
                                :datetime="note.updatedAt"
                                class="shrink-0 text-xs text-[#84909d] dark:text-[#89989c]"
                            >
                                {{ formatNoteDate(note.updatedAt) }}
                            </time>
                        </div>

                        <div
                            class="mt-2.5 flex items-center justify-between gap-3"
                        >
                            <span
                                class="max-w-full truncate rounded-md bg-[#e4f3f4] px-2 py-0.5 text-xs font-medium text-[#08747c] dark:bg-[#123b3e] dark:text-[#6ed3d8]"
                            >
                                {{ note.groupName }}
                            </span>
                            <ArrowRight
                                class="size-4 shrink-0 text-[#087b85] opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100 dark:text-[#6ed3d8]"
                            />
                        </div>
                    </Link>
                </div>

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-md bg-[#eef5f6] text-[#71808c] dark:bg-[#192527] dark:text-[#9ba9ad]"
                    >
                        <FileText class="size-5" />
                    </span>
                    <h3
                        class="mt-4 text-sm font-semibold text-[#17212b] dark:text-[#f1f6f6]"
                    >
                        No notes yet
                    </h3>
                    <p class="mt-1 text-sm text-[#697789] dark:text-[#9ba9ad]">
                        Create a note to keep your latest study thoughts close.
                    </p>
                    <Link
                        :href="notesHref"
                        class="mt-4 flex items-center gap-1.5 text-sm font-medium text-[#087b85] outline-hidden hover:text-[#056670] focus-visible:ring-2 focus-visible:ring-[#078c96]/35 dark:text-[#6ed3d8] dark:hover:text-[#8ce4e8]"
                    >
                        Create note
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </section>

            <div
                class="relative min-h-80 min-w-0 overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-lg border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
