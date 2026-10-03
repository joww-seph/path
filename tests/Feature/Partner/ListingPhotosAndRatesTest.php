<?php

namespace Tests\Feature\Partner;

use App\Http\Controllers\Listings\ListingPhotoController;
use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingPhotosAndRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_photos_are_stored_as_resized_webp_files(): void
    {
        Storage::fake('public');
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->draft()->create();

        $this->actingAs($business->owner)->post(route('listings.photos.store', $listing), [
            'photos' => [UploadedFile::fake()->image('dunes.jpg', 2400, 1600)],
        ])->assertSessionHasNoErrors();

        $photo = $listing->photos()->sole();
        Storage::disk('public')->assertExists([$photo->path, $photo->thumbnail_path]);
        $this->assertStringEndsWith('.webp', $photo->path);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($photo->path));
        [$thumbWidth] = getimagesizefromstring(Storage::disk('public')->get($photo->thumbnail_path));
        $this->assertSame(1600, $width);
        $this->assertSame(480, $thumbWidth);
    }

    public function test_deleting_a_photo_removes_its_files(): void
    {
        Storage::fake('public');
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();
        $this->actingAs($business->owner)->post(route('listings.photos.store', $listing), [
            'photos' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ]);
        $photo = $listing->photos()->sole();

        $this->actingAs($business->owner)->delete(route('listings.photos.destroy', [$listing, $photo]))->assertRedirect();

        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing([$photo->path, $photo->thumbnail_path]);
    }

    public function test_non_images_and_too_many_photos_are_rejected(): void
    {
        Storage::fake('public');
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();

        $this->actingAs($business->owner)->post(route('listings.photos.store', $listing), [
            'photos' => [UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');

        $tooMany = array_fill(0, ListingPhotoController::MAX_PHOTOS + 1, UploadedFile::fake()->image('a.jpg', 800, 600));
        $this->actingAs($business->owner)->post(route('listings.photos.store', $listing), ['photos' => $tooMany])
            ->assertSessionHasErrors('photos');

        $this->assertSame(0, $listing->photos()->count());
    }

    public function test_another_partner_cannot_upload_photos(): void
    {
        Storage::fake('public');
        $listing = Listing::factory()->forBusiness()->create();
        $intruder = Business::factory()->approved()->create()->owner;

        $this->actingAs($intruder)->post(route('listings.photos.store', $listing), [
            'photos' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ])->assertForbidden();
    }

    public function test_a_partner_manages_rates_on_their_listing(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();

        $this->actingAs($business->owner)->post(route('listings.rates.store', $listing), [
            'name' => '4x4 ride, up to 5 people',
            'price' => 2500,
            'unit' => 'ride',
            'capacity' => 5,
        ])->assertSessionHasNoErrors();

        $rate = $listing->rates()->sole();
        $this->assertTrue($rate->is_active);

        $this->actingAs($business->owner)->put(route('listings.rates.update', [$listing, $rate]), [
            'name' => '4x4 ride, up to 5 people',
            'price' => 2800,
            'unit' => 'ride',
            'is_active' => 0,
        ])->assertSessionHasNoErrors();

        $rate->refresh();
        $this->assertSame('2800.00', $rate->price);
        $this->assertFalse($rate->is_active);
    }

    public function test_a_rate_from_another_listing_cannot_be_changed_through_this_one(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();
        $otherRate = ListingRate::factory()->create();

        $this->actingAs($business->owner)
            ->delete(route('listings.rates.destroy', [$listing, $otherRate]))
            ->assertNotFound();

        $this->assertModelExists($otherRate);
    }
}
