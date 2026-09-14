import { usePage } from '@inertiajs/vue3';
import { CalendarClock, ClipboardList, Upload, Users } from '@lucide/vue';
import { computed } from 'vue';
import { index as contactsIndex } from '@/actions/App/Http/Controllers/OnceOffCampaignContactController';
import { index as uploadsIndex } from '@/actions/App/Http/Controllers/OnceOffCampaignContactImportController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import { show as showSchedule } from '@/actions/App/Http/Controllers/OnceOffCampaignScheduleController';
import { usePermissions } from '@/composables/usePermissions';
import type { Campaign } from '../types';

export function useCampaignTabs() {
    const page = usePage<{ campaign: Campaign }>();
    const { hasPermissions } = usePermissions();
    const tabs = computed(() =>
        [
            {
                id: 'summary',
                label: 'Summary',
                icon: ClipboardList,
                href: show.url(page.props.campaign.id),
                pages: ['campaigns/once-off/Show'],
                permissions: ['campaigns.view'],
            },
            {
                id: 'contacts',
                label: 'Contacts',
                icon: Users,
                href: contactsIndex.url(page.props.campaign.id),
                pages: ['campaigns/once-off/Contacts'],
                permissions: ['campaigns.view'],
            },
            {
                id: 'upload_contacts',
                label: 'Upload Contacts',
                icon: Upload,
                href: uploadsIndex.url(page.props.campaign.id),
                pages: [
                    'campaigns/once-off/UploadContacts',
                    'campaigns/once-off/UploadShow',
                ],
                permissions: ['campaigns.view', 'campaigns.edit'],
            },
            {
                id: 'schedule',
                label: 'Schedule',
                icon: CalendarClock,
                href: showSchedule.url(page.props.campaign.id),
                pages: ['campaigns/once-off/Schedule'],
                permissions: ['campaigns.view'],
            },
        ].filter((tab) => hasPermissions(tab.permissions, true)),
    );
    const selectedTab = computed(
        () => tabs.value.find((tab) => tab.pages.includes(page.component))?.id,
    );

    return { tabs, selectedTab };
}
