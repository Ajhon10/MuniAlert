import { api, identityBlob } from './api';
export type Policies = { version: string; operator: string | null; contact: string | null; terms: string[]; privacy: string[] };
export type Verification = { account_verified: boolean; identity_verified: boolean; account_ready: boolean; can_administer: boolean; email_verified: boolean; policies_accepted: boolean; policy_version: string; identity_status: string; identity_reason: string | null; identity_id: string | null; identity_available: boolean; mail_available: boolean; can_report: boolean; document_types: Record<string, string> };
export const identityLabels: Record<string, string> = { not_submitted: 'Not submitted', pending: 'Awaiting administrator review', approved: 'Approved', rejected: 'Needs resubmission', expired: 'Expired — please submit again', withdrawn: 'Withdrawn', name_changed: 'Your account name changed — submit a matching ID' };
export const identityReasons: Record<string, string> = { unreadable: 'The image was not readable.', name_mismatch: 'The name did not match the account.', unsupported_document: 'The document type was not supported.', cannot_verify: 'The administrator could not verify this document. Contact the operator for review.' };
export async function identityImage(id: string): Promise<Blob> {
  if (!/^[a-f0-9-]{36}$/.test(id)) throw new Error('Invalid identity submission.');
  return identityBlob(`/api/admin/identities/${id}/image`);
}
export async function loadVerification() { return api<Verification>('/api/account/verification'); }
