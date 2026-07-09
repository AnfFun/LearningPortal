<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [], hideDesktopHeader = false } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    hideDesktopHeader?: boolean;
}>();
</script>

<template>
    <AppLayout
        :breadcrumbs="breadcrumbs"
        :hide-desktop-header="hideDesktopHeader"
    >
        <slot />
    </AppLayout>
</template>
