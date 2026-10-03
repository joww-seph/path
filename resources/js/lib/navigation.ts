import {
    Activity,
    BellRing,
    LifeBuoy,
    MessageSquareText,
    Phone,
    Siren,
    TrendingUp,
    BarChart3,
    BadgeCheck,
    Building2,
    CalendarDays,
    Compass,
    HeartPulse,
    LayoutGrid,
    Map as MapIcon,
    QrCode,
    Ticket,
    MapPinned,
    SlidersHorizontal,
    Store,
    Users,
} from '@lucide/vue';
import { explore, hotlines } from '@/routes';
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
                        {
                            title: 'My trips',
                            href: tourist.trips.index(),
                            icon: MapIcon,
                        },
                        {
                            title: 'My bookings',
                            href: tourist.bookings.index(),
                            icon: Ticket,
                        },
                        {
                            title: 'Explore Paoay',
                            href: explore(),
                            icon: Compass,
                        },
                    ],
                },
                {
                    label: 'Safety',
                    items: [
                        {
                            title: 'SOS / Get help',
                            href: tourist.sos(),
                            icon: LifeBuoy,
                        },
                        {
                            title: 'Emergency hotlines',
                            href: hotlines(),
                            icon: Phone,
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
                            title: 'My listings',
                            href: partner.listings.index(),
                            icon: Store,
                        },
                        {
                            title: 'Bookings',
                            href: partner.bookings.index(),
                            icon: Ticket,
                        },
                        {
                            title: 'Check in a guest',
                            href: partner.checkIn.scanner(),
                            icon: QrCode,
                        },
                        {
                            title: 'Reviews',
                            href: partner.reviews.index(),
                            icon: MessageSquareText,
                        },
                        {
                            title: 'Reports',
                            href: partner.reports(),
                            icon: BarChart3,
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
                        {
                            title: 'Emergency hotlines',
                            href: admin.hotlines.index(),
                            icon: Phone,
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
                {
                    title: 'Partner verification',
                    href: office.partners.index(),
                    icon: BadgeCheck,
                },
                {
                    title: 'Listings',
                    href: office.listings.index(),
                    icon: MapPinned,
                },
                {
                    title: 'Events',
                    href: office.events.index(),
                    icon: CalendarDays,
                },
                {
                    title: 'Reviews',
                    href: office.reviews.index(),
                    icon: MessageSquareText,
                },
                {
                    title: 'Visitor analytics',
                    href: office.analytics(),
                    icon: TrendingUp,
                },
            ],
        },
        {
            label: 'Safety',
            items: [
                {
                    title: 'Advisories',
                    href: office.advisories.index(),
                    icon: BellRing,
                },
                {
                    title: 'SOS monitor',
                    href: office.sos.index(),
                    icon: Siren,
                },
            ],
        },
    ];
}
