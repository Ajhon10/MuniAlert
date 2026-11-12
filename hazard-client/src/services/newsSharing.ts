import type { Hazard } from '../types';
import { API_SERVER_URL } from '../config/api';

export function newsLink(report: Hazard) { return new URL(`${API_SERVER_URL}/news/${encodeURIComponent(report.id)}`, location.origin).toString(); }
export function newsShareText(report: Hazard) {
  if (!report.fact_checked_at || !report.published_at || report.approximate_location === false) throw new Error('Only the reviewed public news projection can be shared.');
  return [report.title, report.description, `Hazard: ${report.category.name}`, `Area: ${report.barangay}`, `Priority: ${report.severity}`,
    report.public_occurred_at ? `Incident: ${new Date(report.public_occurred_at).toLocaleString('en-PH', { timeZone: 'Asia/Manila' })} PHT` : '',
    report.public_reporter_name ? `Submitted by: ${report.public_reporter_name}` : '',
    `Responsible office: ${report.department?.name || 'Municipal response team'}`, `Reference: ${report.reference_number}`, 'Verified municipal update · MuniAlert'].filter(Boolean).join('\n');
}
export function platformShareUrl(platform: 'facebook' | 'x' | 'whatsapp', report: Hazard) {
  const url = newsLink(report); const text = newsShareText(report);
  return platform === 'facebook' ? `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
    : platform === 'x' ? `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`
      : `https://wa.me/?text=${encodeURIComponent(text + '\n' + url)}`;
}
