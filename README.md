# PaTH — Paoay Travel Hub

PaTH is a mobile-first travel app (PWA) for Paoay, Ilocos Norte. It's built from `public/PaTH-Project-Write-Up.md` and `public/PaTH-Development-Gameplan.md`. Tourists use it to explore the town's sites, plan and share itineraries, track a shared budget, book local services with QR vouchers, and get help in an emergency, even when they have no signal. Local businesses manage their listings and bookings. The Municipal Tourism Office verifies partners, posts advisories, watches SOS alerts and reads visitor analytics.

## Stack

- Laravel 13 (PHP 8.3+), Fortify, Socialite (Google), queues and the scheduler
- Inertia 3, Vue 3 (TypeScript), Tailwind CSS 4, shadcn-vue, Wayfinder
- Leaflet with OpenStreetMap tiles, `vite-plugin-pwa` (a custom service worker), and localforage for offline data
- dompdf for vouchers and itinerary PDFs, bacon-qr-code, html5-qrcode
- PHPUnit feature tests, Pint, and `vp` (oxlint + prettier)

## Getting started

```sh
composer setup      # installs dependencies, creates .env and the SQLite database, migrates and seeds, builds assets
composer dev        # starts the server, queue worker, logs and Vite
```

Then open http://localhost:8000.

### Demo accounts

Every demo account uses the password `password`.

| Role                            | Email                |
| ------------------------------- | -------------------- |
| Tourist                         | tourist@path.test    |
| Partner (verified business)     | partner@path.test    |
| Partner (awaiting verification) | newpartner@path.test |
| Tourism officer                 | office@path.test     |
| Administrator                   | admin@path.test      |

The seeders load Paoay's real heritage sites, stories, festivals and emergency hotlines. They also load demo businesses and itinerary templates. **Check every hotline number before a pilot.**

## Roles and areas

| Area                                                                                | URL prefix                                      | Who                         |
| ----------------------------------------------------------------------------------- | ----------------------------------------------- | --------------------------- |
| Destination guide, map, events, hotlines                                            | `/`, `/explore`, `/map`, `/events`, `/hotlines` | everyone                    |
| Trips, budget, bookings, SOS, reviews                                               | `/my`                                           | tourists                    |
| Listings, bookings, check-in, availability, reports, reviews                        | `/partner`                                      | partners                    |
| Partner verification, listings, events, advisories, SOS monitor, reviews, analytics | `/office`                                       | tourism officers and admins |
| Users and roles, activity log, hotlines                                             | `/admin`                                        | admins                      |

## Optional integrations

The app works without any API keys. Each integration below is turned off until you set its key in `.env`:

| Variable                                   | What it turns on                                      | Without it                            |
| ------------------------------------------ | ----------------------------------------------------- | ------------------------------------- |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | "Continue with Google"                                | the button is hidden                  |
| `SEMAPHORE_API_KEY`                        | SMS for phone OTP and SOS texts (Semaphore, PH)       | texts are written to the log          |
| `OPENROUTESERVICE_API_KEY`                 | real road travel times between stops                  | estimates from straight-line distance |
| `OPENWEATHERMAP_API_KEY`                   | 5-day forecast and rain/storm warnings in the planner | no weather                            |
| `VITE_MAP_TILE_URL`                        | a different tile provider                             | OpenStreetMap                         |

Mail (booking updates, advisories, SOS emails to contacts) uses the normal Laravel `MAIL_*` settings.

Set `PRIVACY_EMAIL` to the Data Protection Officer's address so it appears on the privacy notice (`/privacy`).

## Background work

Run a queue worker (`php artisan queue:work`) and the scheduler (`php artisan schedule:work`, or cron `* * * * * php artisan schedule:run`):

- expire booking requests that partners haven't answered (every 15 minutes)
- mark no-shows (daily, 00:30)
- send trip reminders the evening before (daily, 18:00)
- send booking reminders about an hour before (every 5 minutes)

## Offline use

Tourists press **Make available offline** on a trip. This saves the itinerary, booking vouchers, hotlines and emergency contacts on the phone. Map tiles and photos they have viewed are cached too. With no signal, `/offline` opens the saved trip. Expenses added offline are queued, then synced to `/api/sync` when the phone reconnects.

## Checks

```sh
composer test            # Pint check + PHPUnit
npm run types:check      # vue-tsc
npx vp check             # lint + formatting for the frontend
composer ci:check        # everything above
```

Interface strings are written in English and translated to Filipino in `lang/fil.json`. Users switch language from the sidebar or the header.
