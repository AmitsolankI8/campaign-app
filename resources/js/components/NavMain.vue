<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
}>();

const { currentUrl, isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

const activeGroupTitle = () =>
    props.items.find((item) =>
        item.children?.some((child) => isCurrentOrParentUrl(child.href)),
    )?.title ?? null;

const openItem = ref<string | null>(activeGroupTitle());

watch(currentUrl, () => {
    openItem.value = activeGroupTitle();
});

const setItemOpen = (title: string, isOpen: boolean) => {
    openItem.value = isOpen ? title : null;
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                as-child
                :open="openItem === item.title"
                @update:open="setItemOpen(item.title, $event)"
            >
                <SidebarMenuItem class="group/collapsible">
                    <template v-if="item.children?.length">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="
                                    item.children.some((child) =>
                                        isCurrentOrParentUrl(child.href),
                                    )
                                "
                                :tooltip="item.title"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="child in item.children"
                                    :key="child.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="
                                            isCurrentOrParentUrl(child.href)
                                        "
                                    >
                                        <Link :href="child.href">
                                            <span>{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>

                    <SidebarMenuButton
                        v-else
                        as-child
                        :is-active="isCurrentUrl(item.href)"
                        :tooltip="item.title"
                        @click="openItem = null"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
