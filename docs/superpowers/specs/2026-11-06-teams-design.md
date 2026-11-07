# Teams — Design

Date: 2026-11-06
Status: Draft (awaiting approval)
Approach chosen: Port Jetstream-style teams into the React starter kit

## Intent

Add first-class teams to Dataflow so users can collaborate: every user gets a personal team at registration, can create more teams, switch the team they are working in, invite people by email, and manage members and roles. Future application records will belong to a team and be scoped by the user's current team.

Dataflow currently has only `User`, Fortify authentication, and the profile/security/appearance settings pages. There is no team concept and no domain data yet.

## Why

The product is intended to support multiple people working together rather than one user's private data. Laravel's established pattern for this is Jetstream's teams feature; we are porting that behaviour into the new React starter kit instead of adopting Jetstream, because Jetstream brings its own stack and UI that conflict with this kit.

## Goals

- Every registered user automatically owns a personal team and has it selected as their current team.
- Users can create additional teams, rename teams they own, and switch their current team.
- Owners can delete non-personal teams.
- Owners and admins can invite people by email and manage members; members can leave a team.
- Invitations are accepted through a signed, emailed link and only by the invited email address.
- Roles are `owner`, `admin`, and `member`, with permissions enforced server-side through a policy.
- The current team and the user's teams are available to every Inertia page.
- Existing and future data can be scoped with `team_id` / `currentTeam`.

## Non-goals

- Billing, plans, seats, or usage limits.
- SSO, SCIM, or enterprise directory sync.
- Team avatars, team slugs/subdomains, nested teams.
- Per-team API tokens or API permissions.
- Audit logs, activity feeds, or notification preferences.
- Moving existing records between teams (no existing domain records).

## Anti-goals

- Any page or endpoint that lets a user read or mutate a team they do not belong to.
- Deleting a personal team, or leaving a user with a dangling `current_team_id`.
- Auto-joining a user to a team because of an invitation addressed to someone else.
- Replacing the starter kit's Fortify/Inertia/shadcn stack with Jetstream.

## Constraints

- PHP 8.4, Laravel 13, Inertia v3, React 19, Fortify, Laravel Wayfinder, Tailwind v4, shadcn/Radix UI already installed.
- Follow existing conventions: settings pages under `resources/js/pages/settings`, controllers under `app/Http/Controllers/Settings`, form requests under `app/Http/Requests`, Wayfinder-generated route helpers in React.
- Use `php artisan make:*` for generated classes. Run Pint and the full check suite before completion.
- No new Composer or npm dependencies are required (`@radix-ui/react-dialog`, `dropdown-menu`, and `label` are already present).
- Email uses the configured mailer; in local development `MAIL_MAILER=log` is acceptable for invitation links.

## Approaches considered

1. **Port Jetstream's teams behaviour into this kit (chosen).** Recreate the schema, actions, policy, and member/invitation flows, then build the UI with this kit's shadcn components. Most code, but it follows Laravel conventions, matches the existing UI, and keeps the current auth stack.
2. **Use a community package (e.g. `jurager/teams`).** Less backend code, but its schema, conventions, and upgrade path become ours, and the React UI still has to be written from scratch. It also adds a dependency the starter kit does not choose.
3. **Lean teams.** Create/switch teams and add already-registered users, no invitations or roles. Much smaller, but it is not the Laravel teams behaviour the product wants and would need replacing later.

## Design

### Data model

New migration set (in this order):

1. `create_teams_table`
   - `id`
   - `user_id` — owner, foreign key to `users`, `cascadeOnDelete`
   - `name` (string)
   - `personal_team` (boolean, default `false`)
   - timestamps
2. `add_current_team_id_to_users_table`
   - `current_team_id` — nullable foreign key to `teams`, `nullOnDelete`
3. `create_team_user_table`
   - `id`
   - `team_id` — foreign key, `cascadeOnDelete`
   - `user_id` — foreign key, `cascadeOnDelete`
   - `role` (string, default `member`) — one of `owner`, `admin`, `member`
   - timestamps
   - unique (`team_id`, `user_id`)
4. `create_team_invitations_table`
   - `id`
   - `team_id` — foreign key, `cascadeOnDelete`
   - `invited_by` — nullable foreign key to `users`, `nullOnDelete` (the notification and accept page show "a former member" if null)
   - `email` (string)
   - `role` (string) — one of `admin`, `member`
   - `token` (string, unique)
   - timestamps
   - unique (`team_id`, `email`)

The owner is recorded both as `teams.user_id` and as a `team_user` row with role `owner`, so membership queries are uniform.

### Models

- `App\Models\Team`
  - `owner()` belongsTo `User`
  - `users()` belongsToMany `User` with pivot `role` and timestamps
  - `members()` alias for `users()` (readability)
  - `invitations()` hasMany `TeamInvitation`
  - `allUsers()` returns members including owner (owner is already in the pivot, so return `users`)
  - `hasUser(User $user): bool`
  - `hasUserWithEmail(string $email): bool`
  - helpers: `isPersonal(): bool`
- `App\Models\TeamInvitation`
  - `team()` belongsTo `Team`
  - `isExpired(): bool` (7-day lifetime, based on `created_at`)
- `App\Models\User` additions
  - `currentTeam()` belongsTo `Team`, `current_team_id`
  - `ownedTeams()` hasMany `Team`, `user_id`
  - `teams()` belongsToMany `Team` with pivot `role`
  - `allTeams()` merges `teams` and `ownedTeams` (owner already has a pivot row, so this is `teams`)
  - `personalTeam(): ?Team`
  - `ownsTeam(Team $team): bool`
  - `belongsToTeam(Team $team): bool`
  - `teamRole(Team $team): ?string`
  - `hasTeamPermission(Team $team, string $permission): bool`
  - `switchTeam(Team $team): void` (sets `current_team_id`)
  - casts: `current_team_id` integer

### Roles and permissions

Central definition in `App\Support\TeamPermissions` (or `App\Providers\AppServiceProvider`), so the policy and UI read one source:

| Permission | owner | admin | member |
|---|---|---|---|
| `team:view` | yes | yes | yes |
| `team:update` | yes | no | no |
| `team:delete` | yes | no | no |
| `team:update-member` | yes | members only | no |
| `team:remove-member` | yes | members only | no |
| `team:invite` | yes | members only | no |
| `team:cancel-invitation` | yes | yes | no |

Rules that the policy/actions enforce:

- The owner cannot be removed, demoted, or have their membership changed.
- An admin can manage `member` rows only; only the owner can promote/demote admins.
- Only the owner can invite someone as `admin`; admins may invite `member` only.
- A member may leave the team (delete their own `team_user` row) but cannot remove anyone else. Admins may leave too.
- An owner cannot leave a team; they must delete a non-personal team or hand it over (hand-over is out of scope).
- Personal teams can be shared and members can join them, but a personal team cannot be deleted and its owner cannot leave it. Invited members can leave a personal team.
- Invitations cannot target an existing member or duplicate a pending invitation for the same email/team.
- Invitation roles are limited to `admin` and `member`.

### Actions

`app/Actions/Teams`:

- `CreateTeam` — creates the team, owner pivot row, and sets it as the creator's current team. Used by registration for the personal team and by the create-team endpoint.
- `UpdateTeamName` — authorises `team:update`, updates the name.
- `DeleteTeam` — authorises `team:delete`, rejects personal teams, deletes the team (members/invitations cascade), and moves affected users' `current_team_id` to their personal team.
- `InviteTeamMember` — authorises `team:invite`, creates a `TeamInvitation` with a random token and `invited_by`, sends `TeamInvitationNotification` to the email address, rejects existing members and duplicate pending invitations, and limits admins to inviting `member`.
- `CancelTeamInvitation` — authorises `team:cancel-invitation`, deletes the invitation.
- `RemoveTeamMember` — authorises `team:remove-member`, enforces the owner/admin rules, and reassigns the removed user's current team if needed.
- `AcceptTeamInvitation` — verifies the authenticated user's email matches the invitation, attaches them with the invitation role, sets the team as current, deletes the invitation.

All actions run in database transactions where more than one row changes.

### Policy

`app/Policies/TeamPolicy.php` — auto-discovered by Laravel:

- `view(User, Team)` — member or owner.
- `update(User, Team)` — `team:update`.
- `updateTeamMember`, `removeTeamMember`, `invite`, `cancelInvitation` — corresponding permissions.
- `delete(User, Team)` — `team:delete` and not personal.

Controllers call `$this->authorize(...)`; React never decides access.

### HTTP layer

`app/Http/Controllers/Settings/TeamController.php`

- `edit(Request, Team)` — show `settings/teams` for the current team (or a team id passed in), including members, invitations, and role options.
- `store(Request)` — create a team via `CreateTeam`.
- `update(Request, Team)` — rename via `UpdateTeamName`.
- `destroy(Request, Team)` — delete via `DeleteTeam`.

`app/Http/Controllers/Settings/TeamMemberController.php`

- `update` — change a member's role (owner only for admin changes).
- `destroy` — remove a member, or leave a team.

`app/Http/Controllers/Settings/TeamInvitationController.php`

- `store` — invite by email.
- `destroy` — cancel a pending invitation.
- `show` — render the invitation page (signed; guests may view it).
- `accept` / `decline` — accept or decline an invitation (signed, authenticated, verified, email-bound).

`app/Http/Controllers/CurrentTeamController.php`

- `update(Request)` — switch the current team. Validates membership with the policy, persists `current_team_id`, redirects back.

Route additions in `routes/settings.php` and `routes/web.php`:

- `GET settings/teams/{team?}` → `teams.edit` (auth + verified)
- `POST teams` → `teams.store`
- `PATCH teams/{team}` → `teams.update`
- `DELETE teams/{team}` → `teams.destroy`
- `PATCH teams/{team}/members/{user}` → `team-members.update`
- `DELETE teams/{team}/members/{user}` → `team-members.destroy` (also used by a member to leave with `user = self`)
- `POST teams/{team}/invitations` → `team-invitations.store`
- `DELETE teams/{team}/invitations/{invitation}` → `team-invitations.destroy`
- `PUT current-team` → `current-team.update`
- `GET team-invitations/{invitation}` (signed; guests allowed) → `team-invitations.show`
- `POST team-invitations/{invitation}/accept` (signed, auth, verified) → `team-invitations.accept`
- `DELETE team-invitations/{invitation}` (signed, auth, verified) → `team-invitations.decline`

Wayfinder regenerates the TypeScript route functions during `npm run build` (already wired through the Vite plugin); the React code imports the generated functions rather than hard-coded URLs.

### Registration hook

Modify `app/Actions/Fortify/CreateNewUser` to wrap user creation and personal-team creation in a transaction:

- create the user;
- create a team named `"{first name}'s Team"`, `personal_team = true`, `user_id = user->id`;
- attach the owner pivot row;
- set `current_team_id`;
- return the user.

Existing registration tests gain an assertion that a personal team exists and is current.

### Current-team resolution

A middleware `App\Http\Middleware\ResolveCurrentTeam` runs after `auth` on the authenticated web group:

- if `current_team_id` is null or points to a team the user no longer belongs to, fall back to their personal team (or first team);
- persist the corrected value when it changes.

`HandleInertiaRequests::share()` adds:

```php
'currentTeam' => fn () => $request->user()?->currentTeam?->only('id', 'name', 'personal_team'),
'teams' => fn () => $request->user()?->allTeams()->map(fn ($team) => [
    'id' => $team->id,
    'name' => $team->name,
    'personal_team' => $team->personal_team,
    'role' => $team->pivot->role,
])->values(),
```

The shape is added to `resources/js/types/auth.ts`.

### UI

- `resources/js/components/team-switcher.tsx` — replaces the plain logo in `app-sidebar.tsx`'s `SidebarHeader`. A Radix dropdown showing the current team, the user's other teams (switch on select), a separator, "Create team", and "Manage team". Creating and switching use Inertia `router.post` / `router.put` and keep scroll state.
- `resources/js/components/create-team-modal.tsx` — dialog with a name field, posts to `teams.store`, closes on success, toast on error.
- `resources/js/pages/settings/teams.tsx` — the team settings page, built from existing `Heading`, `Button`, `Input`, `Label`, `InputError`, `Dialog`, `Select`, `Badge` components:
  - **Team name** — owner-only form (`PATCH teams/{team}`).
  - **Members** — list with name, email, role; owner/admin see role select and remove; members see "Leave team".
  - **Pending invitations** — owner/admin only; invite form (email + role) and cancel buttons.
  - **Danger zone** — owner-only delete for non-personal teams, with confirmation.
- `resources/js/pages/teams/accept-invitation.tsx` — shows the team name and inviter, with Accept and Decline buttons. Guests see sign-in/register actions that return them to this page. If the authenticated email does not match the invitation, show a clear error instead of the buttons. If the invitation has expired, show an expired state and no actions.
- Add "Teams" to `resources/js/layouts/settings/layout.tsx` nav items.
- Personal teams show no delete/leave controls.
- Empty states: a user with no non-personal teams sees a "Create your first team" prompt in the switcher; a team with no invitations shows an empty state.

### Invitation email and acceptance

`app/Notifications/TeamInvitationNotification.php` — mail notification with:

- the team name,
- the inviter's name (or "a former member" if `invited_by` is null),
- a temporary signed URL to `team-invitations.show`,
- plain-language expiry ("This invitation expires in 7 days").

The signed link is valid for **7 days**, matching the invitation lifetime. An expired invitation renders the invitation page in an `expired` state with no accept/decline buttons, and the accept endpoint returns 403.

Guest flow:

- `team-invitations.show` is signed and reachable by guests.
- A guest sees the team name and inviter, with "Sign in" and "Create an account" buttons that carry the current URL as the intended destination.
- The register page receives the invitation's email as a prefill and the invitation token, so the new account is created with the invited address. The accept action independently verifies the authenticated user's email matches the invitation.
- After signing in or registering and verifying their email, the user is returned to the signed invitation page to accept or decline.
- An authenticated user whose email does not match sees a clear "This invitation is for a different email address" message and no action buttons.

Uses the existing mail configuration; no new dependency.

### Account deletion

`ProfileController@destroy` currently deletes the user directly. It will delegate to a `DeleteUser` action that, before deleting the user:

- deletes every non-personal team the user owns through the same cleanup as `DeleteTeam`, so members fall back to their personal team;
- lets the personal team and pivot/invitation rows cascade.

This keeps the existing "delete my account" UI working without silently orphaning other members.

### Existing users

Add `php artisan teams:backfill` (command in `app/Console/Commands`) that creates a personal team for every user who has none, attaches the owner pivot row, and sets `current_team_id`. `ResolveCurrentTeam` also lazily creates a personal team if a user somehow has none, so requests never break. Run the command as part of deployment; update the database seeder to create personal teams for seeded users.

### Factories and seeders

- `TeamFactory` — name, owner (`User::factory()`), `personal_team = false`; a `personal()` state.
- `TeamInvitationFactory` — team, email, role, token.
- `UserFactory::withPersonalTeam()` state (creates the user, then a personal team and pivot row, sets `current_team_id`) so feature tests can opt in without changing every existing factory call.

### Testing

Feature tests under `tests/Feature/Teams`:

- registration creates a personal team and sets it current;
- a user can create, rename, and switch teams;
- a member can view their team but cannot rename/invite/delete (403);
- an admin can invite/remove members but cannot delete the team or manage the owner;
- an owner can promote/demote admins, remove members, cancel invitations, and delete a non-personal team;
- a personal team cannot be deleted;
- an owner cannot leave a non-personal team; admins and members can leave;
- an admin cannot invite someone as `admin`; the owner can;
- a guest can open a signed invitation, register with the invited email, verify, and then accept;
- an expired or tampered invitation link is rejected;
- inviting an existing member or duplicating an invitation fails validation;
- accepting an invitation with the wrong email is forbidden;
- accepting an invitation adds the user with the invited role and switches their current team;
- declining an invitation deletes it (and only the invited user can decline);
- a user cannot view or mutate a team they do not belong to;
- deleting a team moves members' current team back to their personal team;
- deleting an account deletes the non-personal teams it owns and leaves other members with a valid current team;
- `teams:backfill` creates missing personal teams and sets `current_team_id`.

Unit tests for role/permission resolution and invitation expiry.

Existing tests must keep passing; `RegistrationTest` gains the personal-team assertion.

### Verification

- `vendor/bin/pint --dirty --format agent`
- `php artisan test` (all tests)
- `composer types:check`
- `npm run check`
- `npm run types:check`
- `npm run build` (also regenerates Wayfinder routes/types)

## Calls made during the builder check

These were open in the first draft; I settled them as follows so the plan is unambiguous. All are easy to change now, before implementation.

1. **Guest invitations are supported.** A signed invitation link works for people without an account; they register with the invited email and accept after verifying.
2. **Owners cannot leave a team.** They delete a non-personal team instead; ownership transfer is out of scope.
3. **Personal teams can be shared.** Members can join and leave them; the personal team still cannot be deleted and its owner cannot leave it.
4. **Only the owner grants admin.** Admins can invite and manage `member` rows only, so they cannot create peers.
5. **No "add existing user" endpoint.** Invitations are the only way in, which removes a redundant controller action and UI.
6. **Account deletion cleans up owned teams.** Deleting your account deletes non-personal teams you own so other members fall back to their personal team.
7. **Existing users are backfilled** with `php artisan teams:backfill`, with a lazy fallback at request time.
8. **Signed invitation links live 7 days**, same as the invitation; expired links show a friendly expired state.
9. **Invitations record who sent them** (`invited_by`), degrading gracefully to "a former member" if that user is deleted.

## Left to the builder

- Exact Tailwind classes, spacing, and icon choices, following the existing settings pages.
- Whether the team settings page is reached only from the switcher or also from the settings sidebar (the design adds it to the sidebar).
- Copy for toasts, empty states, and validation messages.
- Test data details (names, emails) beyond the behaviours listed.
- Migration filenames/timestamps.
