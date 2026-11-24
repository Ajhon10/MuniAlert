<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class PolicyController extends Controller
{
    public function index(): array
    {
        return ['version' => config('onboarding.policy_version'), 'operator' => config('onboarding.operator'), 'contact' => config('onboarding.contact'),
            'terms' => [
                'Use BANTAY / MuniAlert to report hazards you observed in good faith. Deliberately fabricated reports, impersonation, harassment and uploading another person’s ID are prohibited. A mistaken good-faith report is not automatically fraud.',
                'Community members and administrators must use their own account, verify a reachable email and accept the current policies. Government-ID verification is optional for both roles and earns a blue verified identity badge on the profile after manual approval; it is not required to report or administer. Administrators also require authenticator-app 2FA and cannot approve their own identity. Another authorized administrator, or an independent designated municipal operator, reviews an administrator’s ID. A badge does not certify a report as true or endorse its author. Administrators separately verify incident facts before publication.',
                'Online submissions do not guarantee monitoring, response or dispatch. For immediate danger, contact emergency services through the approved hotline directory; do not put yourself at risk to collect evidence.',
                'Public updates contain separately reviewed information. Private reports remain available to their reporter and authorized administrators. Administrators may reject reports or suspend abusive accounts. Contact the operator to request review of an identity decision.',
            ],
            'privacy' => [
                'We process account name, email, hashed password, security/device records, policy acceptance and your submitted reports, photos and location evidence to manage accounts, review hazards and coordinate municipal response. Optional push, location and report emails use your settings and permissions.',
                'With your separate consent, an authorized administrator reviews one Philippine government ID image and its type to compare its name/photo with your account. Keep the name, photograph, issuing authority and document type visible; cover the ID number, QR/barcode, signature, birth date and address. We do not ask for an ID number, selfie, biometrics or access to a government registry. Images are never sent to an AI or ID-verification vendor.',
                'ID images are encrypted in private server storage, available only to authorized administrators during pending review, and deleted immediately after approval/rejection/withdrawal. Pending images expire after seven days and are removed by scheduled cleanup. Backups may retain encrypted copies until the operator’s backup rotation completes. The minimal status, document type, account name, consent version/time and reviewer/time remain while the account exists for verification accountability.',
                'Identity verification is voluntary. Declining, awaiting, failing or withdrawing ID review does not prevent reporting, confirmation or administration when email/policy and role-security requirements are met. Withdrawal deletes any pending image and removes the profile badge. Contact the operator for requests concerning retained review records.',
                'Only you and authorized municipal administrators can access your private evidence and exact location. Reviewed news may credit your display name when explicitly approved; email/contact details and government IDs are never public. Hosting, configured mail, map and push providers process data needed for their service; incident photos may use configured private R2 storage. ID images stay in the server’s separate private identity storage.',
                'Security-event records are normally retained for 30 days (configurable from one to 90 days) under the configured security retention setting. Account and incident records remain for municipal case handling and applicable record-keeping duties; contact the operator for the applicable schedule, access, correction, objection, deletion or portability requests and complaints. You may also contact the Philippine National Privacy Commission. No automated decision makes the identity approval.',
            ]];
    }
}
