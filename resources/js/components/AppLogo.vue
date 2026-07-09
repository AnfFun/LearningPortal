<script setup lang="ts">
import { BookOpenText } from '@lucide/vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center text-[#078c96] group-data-[collapsible=icon]:size-8"
    >
        <BookOpenText class="size-7 stroke-[1.8]" />
    </div>
    <div
        class="ml-1 flex flex-1 items-baseline text-left text-lg leading-none font-semibold group-data-[collapsible=icon]:hidden"
    >
        <span class="text-[#078c96]">Learning</span>
        <span class="text-[#17212b] dark:text-sidebar-foreground">Portal</span>
    </div>
</template>
