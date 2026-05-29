# Data Subject Request Checklist

## Access (Art. 15)

1. Verify subject identity.
2. Run `app(Events::class)->exportPersonalData($user);`.
3. Serialize to JSON or CSV and deliver within 30 days.

## Erasure / right to be forgotten (Art. 17)

1. Verify subject identity.
2. Confirm no overriding legal basis to retain (financial records typically must be kept under accounting law).
3. Run `app(Events::class)->anonymizePersonalData($user);`.
4. Confirm completion in writing to the subject.

## Rectification (Art. 16)

1. Update the relevant row directly (e.g. `Attendee.profile`, `Order` fields).
2. Confirm completion to the subject.

## Restriction of processing (Art. 18)

Add a tag in the subject's account that your application logic respects. The module itself has no built-in restriction flag.

## Portability (Art. 20)

Use the same `exportPersonalData` helper. Provide JSON.
