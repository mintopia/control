<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Clan
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $invite_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|Clan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperClan {}
}

namespace App\Models{
/**
 * App\Models\ClanMembership
 *
 * @property int $id
 * @property int $user_id
 * @property int $clan_id
 * @property int $clan_role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Clan $clan
 * @property-read \App\Models\ClanRole $role
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereClanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereClanRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereUserId($value)
 * @mixin \Eloquent
 */
	class IdeHelperClanMembership {}
}

namespace App\Models{
/**
 * App\Models\ClanRole
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperClanRole {}
}

namespace App\Models{
/**
 * App\Models\EmailAddress
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string|null $verification_code
 * @property \Illuminate\Support\Carbon|null $verification_sent_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $linkedAccounts
 * @property-read int|null $linked_accounts_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerificationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerifiedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperEmailAddress {}
}

namespace App\Models{
/**
 * App\Models\Event
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $draft
 * @property string|null $boxoffice_url
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int $seating_locked
 * @property \Illuminate\Support\Carbon|null $seating_opens_at
 * @property \Illuminate\Support\Carbon|null $seating_closes_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMapping> $mappings
 * @property-read int|null $mappings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatingPlan> $seatingPlans
 * @property-read int|null $seating_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketType> $ticketTypes
 * @property-read int|null $ticket_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereBoxofficeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDraft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingClosesAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingOpensAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperEvent {}
}

namespace App\Models{
/**
 * App\Models\EventMapping
 *
 * @property int $id
 * @property int $event_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\TicketProvider $provider
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperEventMapping {}
}

namespace App\Models{
/**
 * App\Models\LinkedAccount
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $email_address_id
 * @property int|null $social_provider_id
 * @property string|null $external_id
 * @property string|null $name
 * @property string|null $avatar_url
 * @property mixed|null $access_token
 * @property mixed|null $refresh_token
 * @property string|null $access_token_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmailAddress|null $email
 * @property-read \App\Models\SocialProvider|null $provider
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereEmailAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereSocialProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUserId($value)
 * @mixin \Eloquent
 */
	class IdeHelperLinkedAccount {}
}

namespace App\Models{
/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperRole {}
}

namespace App\Models{
/**
 * App\Models\Seat
 *
 * @property int $id
 * @property int $seating_plan_id
 * @property int|null $ticket_id
 * @property int $x
 * @property int $y
 * @property string $row
 * @property int $number
 * @property string $label
 * @property string|null $description
 * @property string|null $class
 * @property int $disabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SeatingPlan $plan
 * @property-read \App\Models\Ticket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder|Seat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereSeatingPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereY($value)
 * @mixin \Eloquent
 */
	class IdeHelperSeat {}
}

namespace App\Models{
/**
 * App\Models\SeatingPlan
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $code
 * @property int $order
 * @property int $revision
 * @property string|null $image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seat> $seats
 * @property-read int|null $seats_count
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereRevision($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperSeatingPlan {}
}

namespace App\Models{
/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $encrypted
 * @property int $hidden
 * @property mixed|null|null $value
 * @property string|null $validation
 * @property \App\Enums\SettingType $type
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 * @mixin \Eloquent
 */
	class IdeHelperSetting {}
}

namespace App\Models{
/**
 * App\Models\SocialProvider
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $provider_class
 * @property int $supports_auth
 * @property int $enabled
 * @property int $auth_enabled
 * @property string|null $client_id
 * @property mixed|null $client_secret
 * @property mixed|null $token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereAuthEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereProviderClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereSupportsAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperSocialProvider {}
}

namespace App\Models{
/**
 * App\Models\Theme
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $readonly
 * @property int $active
 * @property int $dark_mode
 * @property string $primary
 * @property string $nav_background
 * @property string $seat_available
 * @property string $seat_disabled
 * @property string $seat_taken
 * @property string $seat_clan
 * @property string $seat_selected
 * @property string|null $css
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Theme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme query()
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereCss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereNavBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereReadonly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatClan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatSelected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereSeatTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Theme whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperTheme {}
}

namespace App\Models{
/**
 * App\Models\Ticket
 *
 * @property int $id
 * @property int $ticket_provider_id
 * @property int $user_id
 * @property int $event_id
 * @property int $ticket_type_id
 * @property string $external_id
 * @property string $name
 * @property string $reference
 * @property string $qrcode
 * @property string|null $transfer_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\TicketProvider $provider
 * @property-read \App\Models\Seat|null $seat
 * @property-read \App\Models\TicketType $type
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereQrcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTransferCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUserId($value)
 * @mixin \Eloquent
 */
	class IdeHelperTicket {}
}

namespace App\Models{
/**
 * App\Models\TicketProvider
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $provider_class
 * @property mixed|null $apikey
 * @property mixed|null $apisecret
 * @property string|null $endpoint
 * @property mixed|null $webhook_secret
 * @property int $enabled
 * @property string $cache_prefix
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMapping> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketTypeMapping> $types
 * @property-read int|null $types_count
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereApikey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereApisecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereCachePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereProviderClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketProvider whereWebhookSecret($value)
 * @mixin \Eloquent
 */
	class IdeHelperTicketProvider {}
}

namespace App\Models{
/**
 * App\Models\TicketType
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $has_seat
 * @property string|null $discord_role_id
 * @property string|null $discord_role_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketTypeMapping> $mappings
 * @property-read int|null $mappings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereDiscordRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereDiscordRoleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereHasSeat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperTicketType {}
}

namespace App\Models{
/**
 * App\Models\TicketTypeMapping
 *
 * @property int $id
 * @property int $ticket_type_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TicketProvider $provider
 * @property-read \App\Models\TicketType $type
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperTicketTypeMapping {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string $nickname
 * @property string|null $name
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $terms_agreed_at
 * @property int $first_login
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property int $suspended
 * @property int|null $primary_email_id
 * @property \Illuminate\Support\Carbon|null $tickets_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $clanMemberships
 * @property-read int|null $clan_memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmailAddress> $emails
 * @property-read int|null $emails_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\EmailAddress|null $primaryEmail
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePrimaryEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTermsAgreedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTicketsSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class IdeHelperUser {}
}

