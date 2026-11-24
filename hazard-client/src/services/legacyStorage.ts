// Read-only compatibility names for migrating existing device records.
// These are never used as the product name or for new stored records.
export const legacyStorage = { session: 'bantay.session', pushToken: 'bantay.push-token' } as const;
