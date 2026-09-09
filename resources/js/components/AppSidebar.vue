<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Settings, UsersRound } from '@lucide/vue';
import { computed } from 'vue';
import { index as preferencesIndex } from '@/actions/App/Http/Controllers/Settings/PreferencesController';
import { edit as systemSettingsEdit } from '@/actions/App/Http/Controllers/Settings/SystemSettingsController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserManagement/UserController';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { hasPermissions } = usePermissions();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        isVisible: true,
    },
    {
        title: 'User Management',
        href: usersIndex(),
        icon: UsersRound,
        isVisible: hasPermissions(['users.view', 'roles.view']),
        children: [
            {
                title: 'Users',
                href: usersIndex(),
                isVisible: hasPermissions(['users.view']),
            },
            {
                title: 'Roles',
                href: rolesIndex(),
                isVisible: hasPermissions(['roles.view']),
            },
        ],
    },
    {
        title: 'Settings',
        href: systemSettingsEdit(),
        icon: Settings,
        isVisible: hasPermissions(['system-settings.view', 'preferences.view']),
        children: [
            {
                title: 'System',
                href: systemSettingsEdit(),
                isVisible: hasPermissions(['system-settings.view']),
            },
            {
                title: 'Preferences',
                href: preferencesIndex(),
                isVisible: hasPermissions(['preferences.view']),
            },
        ],
    },
]);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
