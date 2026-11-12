
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue';
import { ShieldCheck, LayoutDashboard, Map, ClipboardList, Bell, TriangleAlert, Clock3, Radio, CheckCircle2, Newspaper, Settings, ChartColumn } from '@lucide/vue';
import { pendingMfa, mfaIntent, recoveryLink, clearAccountSecurity } from '../services/accountSecurity';
import { notify, useFeedback } from '../services/feedback';
import { bindReadingAccount } from '../services/readingAccount';
import { api, clearToken } from '../services/api';
import { categories as demoCategories, departments as demoDepartments, demoReports, countStats } from '../data/demo';
import { type Category, type Department, type Hazard, type Notice, type Page, type Stats, type User, labels, relativeTime, active } from '../types';
import { enablePush, unregisterDevice } from '../services/notificationService';
import municipalityConfig from '../config/municipality.json';
import { municipalSettings, loadMunicipalSettings } from '../services/municipalSettings';
import { cancelSocialAuth, initializeSocialAuth, socialError, socialMessage, type SocialResult } from '../services/socialAuth';



export function useDashboard() {
type View = 'overview' | 'map' | 'reports' | 'mine' | 'activity' | 'news' | 'cms' | 'profile' | 'analytics' | 'guide' | 'settings';
const view = ref<View>(new URLSearchParams(location.search).has('news') ? 'news' : 'overview'); const mobileNav = ref(false); const demo = ref(new URLSearchParams(location.search).get('demo') === '1');
const pendingView = ref<View | null>(null);
const user = ref<User | null>(null); const reports = ref<Hazard[]>([]); const categories = ref<Category[]>(demoCategories); const departments = ref<Department[]>(demoDepartments);
watch(() => user.value?.id ?? null, () => bindReadingAccount(user.value), { immediate: true, flush: 'sync' });
const stats = ref<Stats>({ total: 0, active: 0, pending: 0, responding: 0, resolved: 0 }); const notices = ref<Notice[]>([]);
const loading = ref(true); const online = ref(false); const error = ref(''); const search = ref(''); const category = ref(''); const status = ref(''); const page = ref(1); const lastPage = ref(1); const total = ref(0);
const inboxReady = ref(false); const noticesOpen = ref(false); const noticeBusy = ref(false); const assistanceNeeded = ref(false);
const modal = ref<'auth' | 'emergency' | 'report' | null>(null); const selected = ref<Hazard | null>(null); const pendingReport = ref(false); const synced = ref(''); const pushBusy = ref(false);
if (recoveryLink.value) modal.value = 'auth';
watch(pendingMfa, value => { if (value) { modal.value = 'auth'; pendingReport.value = mfaIntent.value === 'report'; } });
const socialIntent = computed(() => pendingReport.value ? 'report' : pendingView.value || view.value);
const municipality = import.meta.env.VITE_MUNICIPALITY || municipalityConfig.office; let refreshTimer: ReturnType<typeof setInterval>; let filterTimer: ReturnType<typeof setTimeout>; let generation = 0;
const citizenNavigation = [{ id: 'overview', label: 'Dashboard', icon: LayoutDashboard }, { id: 'map', label: 'Hazard map', icon: Map }, { id: 'news', label: 'Verified news', icon: Newspaper }, { id: 'mine', label: 'My reports', icon: ShieldCheck }] as const;
const adminNavigation = [{ id: 'overview', label: 'Dashboard', icon: LayoutDashboard }, { id: 'map', label: 'Operations map', icon: Map }, { id: 'reports', label: 'Review reports', icon: ClipboardList }, { id: 'news', label: 'Community news', icon: Newspaper }, { id: 'analytics', label: 'Incident summary', icon: ChartColumn }, { id: 'cms', label: 'Content management', icon: Settings }] as const;
const navigation = computed(() => user.value?.role === 'admin' ? adminNavigation : citizenNavigation);
const heading = computed(() => ({ overview: user.value?.role === 'admin' ? 'Municipal dashboard' : 'Community dashboard', map: user.value?.role === 'admin' ? 'Operations hazard map' : 'Community hazard map', reports: user.value?.role === 'admin' ? 'Report review' : 'Verified community hazards', mine: 'My reports', activity: 'Notifications', news: 'Verified community news', cms: 'Content management', profile: 'My profile & badges', analytics: 'Incident summary', settings: 'Settings', guide: 'Guide & user manual' }[view.value]));
const filtered = computed(() => reports.value.filter(r => (!category.value || r.category.id === category.value) && (!status.value || r.status === status.value) && (!search.value || `${r.title} ${r.reference_number} ${r.barangay} ${r.address}`.toLowerCase().includes(search.value.toLowerCase()))));
const urgent = computed(() => filtered.value.filter(r => active(r.status)).slice(0, 3));
const unread = computed(() => notices.value.filter(n => !n.read_at).length);
const metricCards = computed(() => [{ label: 'Active hazards', value: stats.value.active, icon: TriangleAlert, color: 'orange', detail: 'Requiring community attention' }, { label: user.value?.role === 'admin' ? 'Awaiting verification' : 'Verified reports', value: user.value?.role === 'admin' ? stats.value.pending : stats.value.total, icon: Clock3, color: 'yellow', detail: user.value?.role === 'admin' ? 'Ready for municipal review' : 'Accepted by your municipality' }, { label: 'Response in progress', value: stats.value.responding, icon: Radio, color: 'blue', detail: 'Teams responding to reports' }, { label: 'Resolved hazards', value: stats.value.resolved, icon: CheckCircle2, color: 'green', detail: 'Safer places for everyone' }]);
useFeedback([error]);
watch(socialError, message => { if (message) notify(message, 'error'); });
async function refresh(quiet = false) {
  const version = ++generation; if (!quiet) loading.value = true; error.value = '';
  if (demo.value) { reports.value = user.value?.role === 'admin' ? demoReports() : demoReports().filter(report => ['verified', 'assigned', 'responding', 'resolved', 'closed'].includes(report.status)); stats.value = countStats(reports.value); total.value = filtered.value.length; lastPage.value = 1; loading.value = false; online.value = false; return; }
  try {
    const params = new URLSearchParams({ page: String(page.value), ...(category.value ? { category: category.value } : {}), ...(status.value ? { status: status.value } : {}), ...(search.value ? { search: search.value } : {}) });
    const endpoint = view.value === 'mine' ? '/api/reports/mine' : user.value?.role === 'admin' ? '/api/admin/reports' : '/api/reports';
    if (view.value === 'mine' && !user.value) { reports.value = []; return; }
    const [catalog, counts, list, inbox] = await Promise.all([api<{ categories: Category[]; departments: Department[] }>('/api/catalog'), api<Stats>(user.value?.role === 'admin' ? '/api/admin/stats' : '/api/stats'), api<Page>(`${endpoint}?${params}`), user.value ? api<Notice[]>('/api/notifications') : Promise.resolve([])]);
    if (version !== generation) return;
    if (!counts || !(['total', 'active', 'pending', 'responding', 'resolved'] as const).every(key => Number.isSafeInteger(counts[key]) && counts[key] >= 0)
      || !Array.isArray(catalog?.categories) || !Array.isArray(catalog?.departments) || !Array.isArray(list?.data)
      || !Number.isSafeInteger(list?.meta?.total) || !Number.isSafeInteger(list?.meta?.last_page)) {
      throw new Error('The municipal server returned incomplete dashboard data. Please retry or contact the administrator.');
    }
    categories.value = catalog.categories; departments.value = catalog.departments; stats.value = counts; reports.value = list.data; total.value = list.meta.total; lastPage.value = list.meta.last_page; online.value = true; synced.value = new Date().toLocaleTimeString('en-PH', { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit' });
    notices.value = inbox; inboxReady.value = true;
  } catch (e) { if (version === generation) { error.value = (e as Error).message; online.value = false; } } finally { if (version === generation) loading.value = false; }
}
function navigate(next: View) { if (next === 'activity') { openNotifications(); return; } if (next === 'reports' && user.value?.role !== 'admin') next = 'map'; const retainMapFilters = ['overview', 'map'].includes(view.value) && ['overview', 'map'].includes(next); if (((next === 'mine') && !demo.value || next === 'profile') && !user.value) { pendingView.value = next; modal.value = 'auth'; mobileNav.value = false; return; } if (['cms', 'analytics'].includes(next) && user.value?.role !== 'admin') return; if (user.value?.role === 'admin' && ['mine', 'activity'].includes(next)) return; view.value = next; const url = new URL(location.href); url.searchParams.delete('news'); if (next === 'overview') url.searchParams.delete('view'); else url.searchParams.set('view', next); if (url.href !== location.href) history.pushState(null, '', url); if (!retainMapFilters) { page.value = 1; search.value = ''; category.value = ''; status.value = ''; } mobileNav.value = false; if (['overview', 'map', 'reports', 'mine', 'activity'].includes(next)) void refresh(); void nextTick(() => { window.scrollTo(0, 0); document.getElementById('main-content')?.focus({ preventScroll: true }); }); }
function cancelAuth() { pendingMfa.value = null; recoveryLink.value = null; void cancelSocialAuth().catch(() => {}); socialError.value = ''; modal.value = null; pendingReport.value = false; pendingView.value = null; }
function startReport() { if (user.value?.role === 'admin') return; assistanceNeeded.value = false; try { sessionStorage.removeItem('munialert-report-urgency'); } catch {} modal.value = 'emergency'; }
function proceedReport(urgent: boolean) { assistanceNeeded.value = urgent; try { sessionStorage.setItem('munialert-report-urgency', JSON.stringify({ urgent, expires: Date.now() + 900000 })); } catch {} if (!user.value && !demo.value) { pendingReport.value = true; modal.value = 'auth'; } else modal.value = 'report'; }
let noticeTimer: ReturnType<typeof setInterval>; let noticeLoading = false;
async function refreshNotices() { if (!user.value || demo.value || noticeLoading) return; const id = user.value.id; const role = user.value.role; noticeLoading = true; try { const inbox = await api<Notice[]>('/api/notifications'); if (user.value?.id === id && user.value.role === role) { notices.value = inbox; inboxReady.value = true; } } catch {} finally { noticeLoading = false; } }
function openNotifications() { mobileNav.value = false; noticesOpen.value = true; void refreshNotices(); }
function signInForNotices() { noticesOpen.value = false; pendingView.value = 'activity'; modal.value = 'auth'; }
function visibleNotices() { if (!document.hidden) void refreshNotices(); }
function clearReportSnapshot(clearFilters = false) { generation++; inboxReady.value = false; noticesOpen.value = false; noticeBusy.value = false; reports.value = []; stats.value = { total: 0, active: 0, pending: 0, responding: 0, resolved: 0 }; notices.value = []; selected.value = null; total.value = 0; lastPage.value = 1; page.value = 1; loading.value = true; if (clearFilters) { search.value = ''; category.value = ''; status.value = ''; } }
async function signedIn(value: User) { if (user.value?.id !== value.id || user.value?.role !== value.role) clearReportSnapshot(); user.value = value; demo.value = false; if (value.role === 'admin') view.value = 'overview'; if (pendingReport.value && value.role === 'citizen') { try { const pending = JSON.parse(sessionStorage.getItem('munialert-report-urgency') || 'null'); assistanceNeeded.value = !!(pending?.expires > Date.now() && pending.urgent); } catch {} modal.value = 'report'; } else modal.value = null; pendingReport.value = false; notify(`Welcome, ${value.name.split(' ')[0]}.`); const destination = pendingView.value; pendingView.value = null; if (destination && value.role === 'citizen') { navigate(destination); if (destination === 'activity') await refresh(); } else await refresh(); }
async function logout() { try { await cancelSocialAuth(); await unregisterDevice(); await api('/api/auth/logout', { method: 'POST' }); await clearToken(); clearAccountSecurity(); clearReportSnapshot(true); user.value = null; navigate('overview'); notify('You have been signed out.'); await refresh(); } catch (e) { notify((e as Error).message, 'error'); } }
async function created(report?: Hazard) { modal.value = null; notify(report ? `${report.reference_number} submitted. You can track it in My reports.` : 'Your confirmation has been recorded.'); await refresh(); if (report) { navigate('mine'); selected.value = report; } }
function toggleDemo(value: boolean) { demo.value = value; view.value = 'overview'; page.value = 1; const url = new URL(location.href); url.searchParams.delete('view'); url.searchParams.delete('news'); if (value) url.searchParams.set('demo', '1'); else url.searchParams.delete('demo'); history.replaceState(null, '', url); void refresh(); notify(value ? 'Demo workspace opened. Reports here are fictional.' : 'Live workspace opened.', 'info'); }
async function push() { pushBusy.value = true; try { await enablePush(notify); notify('Push notifications are enabled on this device.'); } catch (e) { notify((e as Error).message, 'error'); } finally { pushBusy.value = false; } }
async function markRead() { const id = user.value?.id; const role = user.value?.role; try { await api('/api/notifications/read', { method: 'PATCH' }); if (user.value?.id !== id || user.value?.role !== role) return; notices.value = notices.value.map(n => ({ ...n, read_at: new Date().toISOString() })); notify('All notifications marked as read.'); } catch (e) { notify((e as Error).message, 'error'); } }
async function openNotice(notice: Notice) {
  if (!user.value || noticeBusy.value) return;
  if (!/^[0-9a-f-]{36}$/i.test(notice.data.report_id || '')) { notify('This notification has no available report.', 'info'); return; }
  const id = user.value.id; const role = user.value.role; noticeBusy.value = true;
  try {
    const report = (await api<{ data: Hazard }>(`/api/reports/${notice.data.report_id}`)).data;
    if (user.value?.id !== id || user.value.role !== role || !noticesOpen.value) return;
    await api(`/api/notifications/${notice.id}/read`, { method: 'PATCH' });
    if (user.value?.id !== id || user.value.role !== role || !noticesOpen.value) return;
    notices.value = notices.value.map(item => item.id === notice.id ? { ...item, read_at: item.read_at || new Date().toISOString() } : item);
    noticesOpen.value = false; navigate(role === 'admin' ? 'reports' : 'mine'); selected.value = report;
  } catch (e) { if (user.value?.id === id) notify((e as Error).message, 'error'); } finally { noticeBusy.value = false; }
}
function exportCsv() { const escape = (value: string) => `"${(/^[=+@\-\t\r]/.test(value) ? "'" : '') + value.replaceAll('"', '""')}"`; const rows = [['Reference', 'Title', 'Barangay', 'Category', 'Status', 'Severity', 'Latitude', 'Longitude'], ...filtered.value.map(r => [r.reference_number, r.title, r.barangay, r.category.name, labels[r.status], r.severity, String(r.verified_latitude ?? r.selected_latitude), String(r.verified_longitude ?? r.selected_longitude)])]; const url = URL.createObjectURL(new Blob(['\uFEFF' + rows.map(row => row.map(escape).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' })); const a = document.createElement('a'); a.href = url; a.download = `munialert-${demo.value ? 'demo-' : ''}reports.csv`; a.click(); URL.revokeObjectURL(url); notify('Report page prepared for download.'); }
async function refreshReports() { await refresh(); if (!error.value) notify(demo.value ? 'Demo reports refreshed.' : 'Reports refreshed.'); }
function expired() { if (!user.value) return; clearAccountSecurity(); notify('Your session has expired. Sign in again to manage your reports.', 'info'); clearReportSnapshot(true); pendingView.value = null; user.value = null; modal.value = null; pendingReport.value = false; view.value = 'overview'; void refresh(); }
async function completeSocial(result: SocialResult) {
  if (result.linked) { user.value = result.user; navigate('profile'); notify('Your sign-in account is connected.'); return; }
  pendingReport.value = result.intent === 'report';
  pendingView.value = ['profile', 'mine', 'activity', 'news', 'reports', 'map', 'guide', 'settings'].includes(result.intent) ? result.intent as View : null;
  await signedIn(result.user);
}
function socialAuthenticated(event: Event) { void completeSocial((event as CustomEvent<SocialResult>).detail); }
async function restoreSocialReturn() {
  const url = new URL(location.href);
  const status = url.searchParams.get('social_auth');
  if (!status) return;
  const intent = url.searchParams.get('social_intent') || 'overview';
  const reason = url.searchParams.get('social_error');
  for (const key of ['social_auth', 'social_intent', 'social_error', 'provider']) url.searchParams.delete(key);
  history.replaceState(null, '', url);
  if (status === 'mfa') { mfaIntent.value = intent; pendingMfa.value = await api('/auth/mfa/pending'); modal.value = 'auth'; return; }
  if ((status === 'success' || status === 'linked') && user.value) await completeSocial({ user: user.value, intent, linked: status === 'linked' });
  else { socialError.value = socialMessage(status === 'error' ? reason : 'expired'); modal.value = 'auth'; }
}
function restoreView(refreshData: unknown = true) {
  const query = new URLSearchParams(location.search);
  const requested = query.has('news') ? 'news' : query.get('view') || 'overview';
  if (requested === 'activity') { const url = new URL(location.href); url.searchParams.delete('view'); history.replaceState(null, '', url); view.value = 'overview'; openNotifications(); if (refreshData !== false) void refresh(); return; }
  const next = requested === 'reports' && user.value?.role !== 'admin' ? 'map' : requested;
  if (next !== requested) { const url = new URL(location.href); url.searchParams.set('view', next); history.replaceState(null, '', url); }
  const allowed = ['guide', 'settings', 'profile'].includes(next) || navigation.value.some(item => item.id === next);
  const requiresLogin = ['mine', 'activity', 'profile'].includes(next);
  view.value = allowed && (!requiresLogin || user.value || demo.value) ? next as View : 'overview';
  mobileNav.value = false; page.value = 1;
  if (refreshData !== false) void refresh();
}
watch([search, category, status], () => { clearTimeout(filterTimer); page.value = 1; filterTimer = setTimeout(() => void refresh(), 350); });
onMounted(async () => { window.addEventListener('session-expired', expired); window.addEventListener('popstate', restoreView); window.addEventListener('social-authenticated', socialAuthenticated); await initializeSocialAuth(); if (!demo.value) { try { user.value = await api<User>('/api/auth/me'); } catch {} } restoreView(false); await restoreSocialReturn(); void loadMunicipalSettings().catch(() => {}); await refresh(); document.addEventListener('visibilitychange', visibleNotices); noticeTimer = setInterval(() => { if (!document.hidden) void refreshNotices(); }, 15000); refreshTimer = setInterval(() => { if (!document.hidden && !modal.value && !selected.value && ['overview', 'map', 'reports', 'mine', 'activity'].includes(view.value)) void refresh(true); }, 60000); });
onBeforeUnmount(() => { clearInterval(noticeTimer); document.removeEventListener('visibilitychange', visibleNotices); clearInterval(refreshTimer); clearTimeout(filterTimer); window.removeEventListener('session-expired', expired); window.removeEventListener('popstate', restoreView); window.removeEventListener('social-authenticated', socialAuthenticated); });

return { inboxReady,noticesOpen,noticeBusy,assistanceNeeded,openNotifications,signInForNotices,proceedReport,view,mobileNav,demo,user,reports,categories,departments,stats,notices,loading,online,error,search,category,status,page,lastPage,total,modal,selected,pendingReport,socialIntent,synced,pushBusy,municipality,navigation,heading,filtered,urgent,unread,metricCards,municipalSettings,notify,refresh,navigate,startReport,cancelAuth,signedIn,logout,created,toggleDemo,push,markRead,openNotice,exportCsv,refreshReports };
}
