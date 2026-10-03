import {
    Activity,
    Building2,
    HeartPulse,
    LayoutGrid,
    SlidersHorizontal,
    Users,
} from '@lucide/vue';
import admin from '@/routes/admin';
import office from '@/routes/office';
import partner from '@/routes/partner';
import tourist from '@/routes/tourist';
import type { NavItem, Role } from '@/types';

export type NavGroup = {
    label: string;
    items: NavItem[];
};

/**
 * The sidebar menu for each role. Titles are English keys passed through t().
 */
export function navigationFor(role: Role): NavGroup[] {
    switch (role) {
        case 'tourist':
            return [
                {
                    label: 'My trip',
                    items: [
                        {
                            title: 'Dashboard',
                            href: tourist.dashboard(),
                            icon: LayoutGrid,
                        },
                    ],
                },
                {
                    label: 'Profile',
                    items: [
                        {
                            title: 'Travel preferences',
                            href: tourist.preferences.edit(),
                            icon: SlidersHorizontal,
                        },
                        {
                            title: 'Emergency contacts',
                            href: tourist.emergencyContacts.index(),
                            icon: HeartPulse,
                        },
                    ],
                },
            ];
        case 'partner':
            return [
                {
                    label: 'Business',
                    items: [
                        {
                            title: 'Dashboard',
                            href: partner.dashboard(),
                            icon: LayoutGrid,
                        },
                        {
                            title: 'Business profile',
                            href: partner.business.edit(),
                            icon: Building2,
                        },
                    ],
                },
            ];
        case 'tourism_officer':
            return officeNavigation();
        case 'admin':
            return [
                {
                    label: 'Administration',
                    items: [
                        {
                            title: 'Dashboard',
                            href: admin.dashboard(),
                            icon: LayoutGrid,
                        },
                        {
                            title: 'Users and roles',
                            href: admin.users.index(),
                            icon: Users,
                        },
                        {
                            title: 'Activity log',
                            href: admin.activity.index(),
                            icon: Activity,
                        },
                    ],
                },
                ...officeNavigation(),
            ];
    }
}

function officeNavigation(): NavGroup[] {
    return [
        {
            label: 'Tourism office',
            items: [
                {
                    title: 'Office dashboard',
                    href: office.dashboard(),
                    icon: LayoutGrid,
                },
            ],
        },
    ];
}
