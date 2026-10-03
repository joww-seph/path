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
