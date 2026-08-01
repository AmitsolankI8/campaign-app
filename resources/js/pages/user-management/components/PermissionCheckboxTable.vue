<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import type { PermissionGroups } from '../types';

defineProps<{
    permissionGroups: PermissionGroups;
    selected: string[];
}>();

const emit = defineEmits<{
    toggle: [permission: string, checked: boolean];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Group</th>
                    <th class="px-4 py-3 font-medium">Permission</th>
                    <th class="w-24 px-4 py-3 text-center font-medium">
                        Assign
                    </th>
                </tr>
            </thead>
            <tbody>
                <template
                    v-for="(permissions, group) in permissionGroups"
                    :key="group"
                >
                    <tr
                        v-for="(permission, index) in permissions"
                        :key="permission.name"
                        class="border-t"
                    >
                        <td class="px-4 py-3 font-medium text-muted-foreground">
                            {{ index === 0 ? group : '' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ permission.label }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ permission.name }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="
                                    selected.includes(permission.name)
                                "
                                @update:model-value="
                                    (value) =>
                                        emit(
                                            'toggle',
                                            permission.name,
                                            value === true,
                                        )
                                "
                            />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
