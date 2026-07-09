<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-5 py-4 group-data-[collapsible=icon]:px-2">
        <SidebarMenu class="gap-3">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="h-11 gap-3 px-3 text-[15px] text-sidebar-foreground/85 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-[#e2f1f3] data-[active=true]:font-semibold data-[active=true]:text-[#087b85] dark:data-[active=true]:bg-[#123b3e] dark:data-[active=true]:text-[#6ed3d8] [&>svg]:size-[18px]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
