<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One account per role for local development and demos. Every password is "password".
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->account('admin@path.test', 'PaTH Administrator', Role::Admin);
        $this->account('office@path.test', 'Tourism Office Staff', Role::TourismOfficer);

        $tourist = $this->account('tourist@path.test', 'Ana Reyes', Role::Tourist);
        $tourist->touristProfile()->updateOrCreate([], [
            'interests' => ['heritage', 'adventure', 'food'],
            'group_size' => 3,
            'budget_min' => 5000,
            'budget_max' => 12000,
            'home_province' => 'Metro Manila',
        ]);
        $tourist->emergencyContacts()->firstOrCreate(
            ['phone' => '09171234567'],
            ['name' => 'Rosa Reyes', 'relationship' => 'Mother'],
        );

        $partner = $this->account('partner@path.test', 'Jun Agcaoili', Role::Partner);
        Business::updateOrCreate(['owner_id' => $partner->id], [
            'name' => 'Paoay Dunes 4x4 Adventures',
            'type' => BusinessType::ActivityOperator,
            'permit_no' => 'BP-2026-00412',
            'contact_phone' => '09181234567',
            'contact_email' => 'partner@path.test',
            'address' => 'Brgy. Suba, Paoay, Ilocos Norte',
            'description' => '4x4 dune rides and sandboarding on the Paoay Sand Dunes.',
            'payment_instructions' => 'Pay on arrival in cash, or by GCash to 0918 123 4567.',
        ])->forceFill([
            'verification_status' => VerificationStatus::Approved,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ])->save();

        $pending = $this->account('newpartner@path.test', 'Liza Ramos', Role::Partner);
        Business::updateOrCreate(['owner_id' => $pending->id], [
            'name' => 'Lakeside Inabel and Pasalubong',
            'type' => BusinessType::Shop,
            'permit_no' => 'BP-2026-00977',
            'contact_phone' => '09191234567',
            'address' => 'Brgy. Nanguyudan, Paoay, Ilocos Norte',
        ]);
    }

    private function account(string $email, string $name, Role $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'password' => 'password']);
        $user->role = $role;
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
