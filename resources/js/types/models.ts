export type Option = {
    value: string;
    label: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type VerificationStatus = 'pending' | 'approved' | 'rejected';

export type Business = {
    id: number;
    owner_id: number;
    name: string;
    type: string;
    permit_no: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    address: string | null;
    description: string | null;
    payment_instructions: string | null;
    verification_status: VerificationStatus;
    verification_note: string | null;
    verified_at: string | null;
    created_at: string;
};

export type TouristProfile = {
    interests: string[] | null;
    group_size: number;
    budget_min: number | null;
    budget_max: number | null;
    accessibility_needs: string[] | null;
    home_province: string | null;
    home_country: string;
};

export type EmergencyContact = {
    id: number;
    name: string;
    relationship: string | null;
    phone: string;
    email: string | null;
};

export type ActivityLogEntry = {
    id: number;
    action: string;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
    user: { id: number; name: string; email?: string } | null;
};

export type ListingStatus =
    | 'draft'
    | 'pending'
    | 'published'
    | 'rejected'
    | 'archived';

export type Category = {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    color?: string;
};

export type ListingCategory = {
    name: string;
    slug: string;
    icon: string;
    color: string;
};

export type ListingCard = {
    id: number;
    name: string;
    slug: string;
    summary: string | null;
    status: ListingStatus;
    category?: ListingCategory;
    business_name?: string | null;
    barangay: string | null;
    latitude: number | null;
    longitude: number | null;
    starting_price: number | null;
    is_free: boolean;
    is_bookable: boolean;
    is_accessible: boolean;
    is_featured: boolean;
    rating_average: number;
    reviews_count: number;
    visit_minutes: number;
    photo?: string | null;
    distance_km?: number;
};

export type OpeningHours = Record<string, { open: string; close: string }>;

export type ListingRate = {
    id: number;
    name: string;
    description: string | null;
    price: string;
    unit: string;
    unit_label: string;
    capacity: number | null;
    is_active: boolean;
};

export type ListingPhoto = {
    id: number;
    url: string;
    thumbnail_url: string;
    caption: string | null;
};

export type HeritageStory = {
    id: number;
    title: string;
    body: string;
    source: string | null;
};

export type ListingDetail = ListingCard & {
    category_id: number;
    business_id: number | null;
    description: string | null;
    barangay_slug: string | null;
    address: string | null;
    opening_hours: OpeningHours | null;
    entrance_fee: string | null;
    price_min: string | null;
    price_max: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    website: string | null;
    facebook_url: string | null;
    review_note: string | null;
    published_at: string | null;
    business?: {
        id: number;
        name: string;
        verification_status: VerificationStatus;
    } | null;
    rates?: ListingRate[];
    photos?: ListingPhoto[];
    heritage_stories?: HeritageStory[];
};

export type Barangay = {
    slug: string;
    name: string;
};

export type LatLng = {
    lat: number;
    lng: number;
};

export type PaoayEvent = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    venue_listing_id: number | null;
    venue_name: string | null;
    starts_at: string;
    ends_at: string | null;
    is_featured: boolean;
    venue?: {
        id: number;
        name: string;
        slug: string;
        address?: string | null;
        latitude?: number | null;
        longitude?: number | null;
    } | null;
};

/**
 * A Laravel API resource collection built from a paginator.
 */
export type ResourcePage<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export type TripRole = 'owner' | 'editor' | 'viewer';

export type Trip = {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    day_count: number;
    pax: number;
    budget: string | null;
    day_starts_at: string;
    travel_mode: 'car' | 'tricycle' | 'walk';
    notes: string | null;
    role: TripRole | null;
    owner?: { id: number; name: string };
    items_count?: number;
    is_shared: boolean;
    share_url?: string;
};

export type ItineraryItem = {
    id: number;
    title: string;
    listing: {
        id: number;
        name: string;
        slug: string;
        category: { name: string; icon: string; color: string } | null;
        photo: string | null;
        entrance_fee: string | null;
        price_min: string | null;
        is_bookable: boolean;
        contact_phone: string | null;
        address: string | null;
    } | null;
    booking: { code: string; status: BookingStatus } | null;
    latitude: number | null;
    longitude: number | null;
    day_number: number;
    position: number;
    duration_minutes: number | null;
    visit_minutes: number;
    fixed_start_time: string | null;
    start_time: string | null;
    end_time: string | null;
    travel_minutes_from_previous: number | null;
    distance_km_from_previous: number | null;
    notes: string | null;
    is_done: boolean;
    updated_at: string;
};

export type ItineraryDay = {
    number: number;
    date: string;
    in_trip: boolean;
    items: ItineraryItem[];
};

export type PlannerWarning = {
    day: number;
    item_id: number | null;
    type: string;
    message: string;
};

export type ItineraryTemplate = {
    id: number;
    name: string;
    slug: string;
    summary: string | null;
    days: number;
    items_count: number;
};

export type TripMember = {
    id: number;
    name: string;
    email: string;
    role: TripRole;
};

export type BookingStatus =
    | 'pending'
    | 'confirmed'
    | 'declined'
    | 'cancelled'
    | 'expired'
    | 'completed'
    | 'no_show';

export type Booking = {
    id: number;
    code: string;
    status: BookingStatus;
    status_label: string;
    rate_name: string;
    unit: string;
    unit_label: string;
    unit_price: string;
    date: string;
    time: string | null;
    nights: number;
    pax: number;
    quantity: number;
    total_amount: string;
    tourist_note: string | null;
    partner_note: string | null;
    expires_at: string | null;
    confirmed_at: string | null;
    checked_in_at: string | null;
    cancelled_at: string | null;
    created_at: string;
    is_cancellable: boolean;
    listing?: {
        id: number;
        name: string;
        slug: string;
        address: string | null;
        contact_phone: string | null;
        latitude: number | null;
        longitude: number | null;
    };
    tourist?: { id: number; name: string; email: string; phone: string | null };
    trip?: { id: number; title: string } | null;
};

export type AdvisorySeverity = 'info' | 'warning' | 'danger';

export type Advisory = {
    id: number;
    title: string;
    body: string;
    severity: AdvisorySeverity;
    starts_at?: string;
    ends_at: string | null;
};

export type Review = {
    id: number;
    rating: number;
    comment: string | null;
    partner_reply: string | null;
    created_at: string;
    author: string;
    is_mine?: boolean;
};

export type DayForecast = {
    date: string;
    min: number;
    max: number;
    condition: string;
    description: string;
    icon: string;
    rain_chance: number;
    warning: string | null;
};
