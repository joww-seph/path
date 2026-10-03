<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<array{phone: string, message: string}>
     */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SmsService::class, new class($this->sent) extends SmsService
        {
            /**
             * @param  list<array{phone: string, message: string}>  $sent
             */
            public function __construct(private array &$sent)
            {
                parent::__construct(null, 'PaTH');
            }

            public function send(string $phone, string $message): void
            {
                $this->sent[] = ['phone' => $phone, 'message' => $message];
            }
        });
    }

    private function sentCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sent)['message'], $matches);

        return $matches[1];
    }

    public function test_a_user_can_verify_their_phone_with_the_texted_code(): void
    {
        $user = User::factory()->create(['phone' => '09171234567']);

        $this->actingAs($user)->post(route('phone.code'))->assertRedirect();

        $this->assertCount(1, $this->sent);
        $this->assertSame('09171234567', $this->sent[0]['phone']);

        $this->actingAs($user)
            ->post(route('phone.verify'), ['code' => $this->sentCode()])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '09171234567']);
        $this->actingAs($user)->post(route('phone.code'));

        $wrong = $this->sentCode() === '000000' ? '111111' : '000000';

        $this->actingAs($user)
            ->post(route('phone.verify'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'That code is wrong or has expired.']);

        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_a_code_stops_working_if_the_phone_number_changes(): void
    {
        $user = User::factory()->create(['phone' => '09171234567']);
        $this->actingAs($user)->post(route('phone.code'));
        $code = $this->sentCode();

        $user->update(['phone' => '09189999999']);

        $this->actingAs($user)
            ->post(route('phone.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_a_code_cannot_be_sent_without_a_phone_number(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)->post(route('phone.code'))->assertSessionHasErrors('phone');

        $this->assertCount(0, $this->sent);
    }

    public function test_changing_the_phone_number_clears_its_verification(): void
    {
        $user = User::factory()->create(['phone' => '09171234567', 'phone_verified_at' => now()]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+63 918 765 4321',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('09187654321', $user->phone);
        $this->assertNull($user->phone_verified_at);
    }
}
