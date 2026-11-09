
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue';
import { ShieldCheck, LayoutDashboard, Map, ClipboardList, Bell, TriangleAlert, Clock3, Radio, CheckCircle2, Newspaper, Settings, UserRound, ChartColumn, BookOpen } from '@lucide/vue';
import { api, clearToken } from '../services/api';
import { categories as demoCategories, departments as demoDepartments, demoReports, countStats } from '../data/demo';
import { type Category, type Department, type Hazard, type Notice, type Page, type Stats, type User, labels, relativeTime, active } from '../types';
import { enablePush, unregisterDevice } from '../services/notificationService';
import municipalityConfig from '../config/municipality.json';
import { municipalSettings, loadMunicipalSettings } from '../services/municipalSettings';
import { cancelSocialAuth, initializeSocialAuth, socialError, socialMessage, type SocialResult } from '../services/socialAuth';



export function useDashboard() {
type View = 'overview' | 'map' | 'reports' | 'mine' | 'activity' | 'news' | 'cms' | 'profile' | 'analytics' | 'guide';
const view = ref<View>(new URLSearchParams(location.search).has('news') ? 'news' : 'overview'); const mobileNav = ref(false); const demo = ref(new URLSearchParams(location.search).get('demo') === '1');
const pendingView = ref<View | null>(null);
const user = ref<User | null>(null); const reports = ref<Hazard[]>([]); const categories = ref<Category[]>(demoCategories); const departments = ref<Department[]>(demoDepartments);
const stats = ref<Stats>({ total: 0, active: 0, pending: 0, responding: 0, resolved: 0 }); const notices = ref<Notice[]>([]);
const loading = ref(true); const online = ref(false); const error = ref(''); const search = ref(''); const category = ref(''); const status = ref(''); const page = ref(1); const lastPage = ref(1); const total = ref(0);
const modal = ref<'auth' | 'report' | null>(null); const selected = ref<Hazard | null>(null); const toast = ref(''); const pendingReport = ref(false); const synced = ref(''); const pushBusy = ref(false);
const socialIntent = computed(() => pendingReport.value ? 'report' : pendingView.value || view.value);
const municipality = import.meta.env.VITE_MUNICIPALITY || municipalityConfig.office; let refreshTimer: ReturnType<typeof setInterval>; let filterTimer: ReturnType<typeof setTimeout>; let toastTimer: ReturnType<typeof setTimeout>; let generation = 0;
const citizenNavigation = [{ id: 'overview', label: 'Overview', icon: LayoutDashboard }, { id: 'map', label: 'Hazard map', icon: Map }, { id: 'reports', label: 'Community reports', icon: ClipboardList }, { id: 'news', label: 'Verified news', icon: Newspaper }, { id: 'mine', label: 'My reports', icon: ShieldCheck }, { id: 'activity', label: 'Notifications', icon: Bell }, { id: 'profile', label: 'My profile', icon: UserRound }, { id: 'guide', label: 'Guide & user manual', icon: BookOpen }] as const;
const adminNavigation = [{ id: 'overview', label: 'Operations', icon: LayoutDashboard }, { id: 'map', label: 'Operations map', icon: Map }, { id: 'reports', label: 'Review reports', icon: ClipboardList }, { id: 'analytics', label: 'Incident summary', icon: ChartColumn }, { id: 'cms', label: 'Content management', icon: Settings }, { id: 'profile', label: 'My profile', icon: UserRound }, { id: 'guide', label: 'Guide & user manual', icon: BookOpen }] as const;
const navigation = computed(() => user.value?.role === 'admin' ? adminNavigation : citizenNavigation);
const heading = computed(() => ({ overview: user.value?.role === 'admin' ? 'Municipal operations' : 'Community overview', map: user.value?.role === 'admin' ? 'Operations hazard map' : 'Community hazard map', reports: user.value?.role === 'admin' ? 'Report review' : 'Hazard reports', mine: 'My reports', activity: 'Notifications', news: 'Verified community news', cms: 'Content management', profile: 'My profile & badges', analytics: 'Incident summary', guide: 'Guide & user manual' }[view.value]));
const filtered = computed(() => reports.value.filter(r => (!category.value || r.category.id === category.value) && (!status.value || r.status === status.value) && (!search.value || `${r.title} ${r.reference_number} ${r.barangay} ${r.address}`.toLowerCase().includes(search.value.toLowerCase()))));
const urgent = computed(() => filtered.value.filter(r => active(r.status)).slice(0, 3));
const unread = computed(() => notices.value.filter(n => !n.read_at).length);
const metricCards = computed(() => [{ label: 'Active hazards', value: stats.value.active, icon: TriangleAlert, color: 'orange', detail: 'Requiring community attention' }, { label: 'Awaiting verification', value: stats.value.pending, icon: Clock3, color: 'yellow', detail: 'Ready for municipal review' }, { label: 'Response in progress', value: stats.value.responding, icon: Radio, color: 'blue', detail: 'Teams responding to reports' }, { label: 'Resolved hazards', value: stats.value.resolved, icon: CheckCircle2, color: 'green', detail: 'Safer places for everyone' }]);
function notify(text: string) { toast.value = text; clearTimeout(toastTimer); toastTimer = setTimeout(() => toast.value = '', 6500); }
watch(socialError, message => { if (message) notify(message); });
async function refresh(quiet = false) {
  const version = ++generation; if (!quiet) loading.value = true; error.value = '';
  if (demo.value) { reports.value = demoReports(); stats.value = countStats(reports.value); total.value = filtered.value.length; lastPage.value = 1; loading.value = false; online.value = false; return; }
  try {
    const params = new URLSearchParams({ page: String(page.value), ...(category.value ? { category: category.value } : {}), ...(status.value ? { status: status.value } : {}), ...(search.value ? { search: search.value } : {}) });
    const endpoint = view.value === 'mine' ? '/api/reports/mine' : user.value?.role === 'admin' ? '/api/admin/reports' : '/api/reports';
    if (view.value === 'mine' && !user.value) { reports.value = []; return; }
    const [catalog, counts, list, inbox] = await Promise.all([api<{ categories: Category[]; departments: Department[] }>('/api/catalog'), api<Stats>('/api/stats'), api<Page>(`${endpoint}?${params}`), user.value ? api<Notice[]>('/api/notifications') : Promise.resolve([])]);
    if (version !== generation) return;
    if (!counts || !(['total', 'active', 'pending', 'responding', 'resolved'] as const).every(key => Number.isSafeInteger(counts[key]) && counts[key] >= 0)
      || !Array.isArray(catalog?.categories) || !Array.isArray(catalog?.departments) || !Array.isArray(list?.data)
      || !Number.isSafeInteger(list?.meta?.total) || !Number.isSafeInteger(list?.meta?.last_page)) {
      throw new Error('The municipal server returned incomplete dashboard data. Please retry or contact the administrator.');
    }
    categories.value = catalog.categories; departments.value = catalog.departments; stats.value = counts; reports.value = list.data; total.value = list.meta.total; lastPage.value = list.meta.last_page; online.value = true; synced.value = new Date().toLocaleTimeString('en-PH', { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit' });
    notices.value = inbox;
  } catch (e) { if (version === generation) { error.value = (e as Error).message; online.value = false; } } finally { if (version === generation) loading.value = false; }
}
function navigate(next: View) { const retainMapFilters = ['overview', 'map'].includes(view.value) && ['overview', 'map'].includes(next); if (((next === 'mine' || next === 'activity') && !demo.value || next === 'profile') && !user.value) { pendingView.value = next; modal.value = 'auth'; mobileNav.value = false; return; } if (['cms', 'analytics'].includes(next) && user.value?.role !== 'admin') return; if (user.value?.role === 'admin' && ['mine', 'activity'].includes(next)) return; view.value = next; const url = new URL(location.href); url.searchParams.delete('news'); if (next === 'overview') url.searchParams.delete('view'); else url.searchParams.set('view', next); if (url.href !== location.href) history.pushState(null, '', url); if (!retainMapFilters) { page.value = 1; search.value = ''; category.value = ''; status.value = ''; } mobileNav.value = false; if (['overview', 'map', 'reports', 'mine', 'activity'].includes(next)) void refresh(); void nextTick(() => { window.scrollTo(0, 0); document.getElementById('main-content')?.focus({ preventScroll: true }); }); }
function cancelAuth() { void cancelSocialAuth().catch(() => {}); socialError.value = ''; modal.value = null; pendingReport.value = false; pendingView.value = null; }
function startReport() { if (user.value?.role === 'admin') return; if (!user.value && !demo.value) { pendingReport.value = true; modal.value = 'auth'; } else modal.value = 'report'; }
async function signedIn(value: User) { user.value = value; demo.value = false; if (value.role === 'admin') view.value = 'overview'; modal.value = pendingReport.value && value.role === 'citizen' ? 'report' : null; pendingReport.value = false; notify(`Welcome, ${value.name.split(' ')[0]}.`); const destination = pendingView.value; pendingView.value = null; if (destination && value.role === 'citizen') navigate(destination); else await refresh(); }
async function logout() { try { await cancelSocialAuth(); await unregisterDevice(); await api('/api/auth/logout', { method: 'POST' }); await clearToken(); user.value = null; notices.value = []; navigate('overview'); notify('You have been signed out.'); await refresh(); } catch (e) { notify((e as Error).message); } }
async function created(report?: Hazard) { modal.value = null; notify(report ? `${report.reference_number} submitted. You can track it in My reports.` : 'Your confirmation has been recorded.'); await refresh(); if (report) selected.value = report; }
function toggleDemo(value: boolean) { demo.value = value; view.value = 'overview'; page.value = 1; const url = new URL(location.href); url.searchParams.delete('view'); url.searchParams.delete('news'); if (value) url.searchParams.set('demo', '1'); else url.searchParams.delete('demo'); history.replaceState(null, '', url); void refresh(); }
async function push() { pushBusy.value = true; try { await enablePush(notify); notify('Push notifications are enabled on this device.'); } catch (e) { notify((e as Error).message); } finally { pushBusy.value = false; } }
async function markRead() { try { await api('/api/notifications/read', { method: 'PATCH' }); notices.value = notices.value.map(n => ({ ...n, read_at: new Date().toISOString() })); } catch (e) { notify((e as Error).message); } }
async function openNotice(notice: Notice) { try { selected.value = (await api<{ data: Hazard }>(`/api/reports/${notice.data.report_id}`)).data; } catch (e) { notify((e as Error).message); } }
function exportCsv() { const escape = (value: string) => `"${(/^[=+@\-\t\r]/.test(value) ? "'" : '') + value.replaceAll('"', '""')}"`; const rows = [['Reference', 'Title', 'Barangay', 'Category', 'Status', 'Severity', 'Latitude', 'Longitude'], ...filtered.value.map(r => [r.reference_number, r.title, r.barangay, r.category.name, labels[r.status], r.severity, String(r.verified_latitude ?? r.selected_latitude), String(r.verified_longitude ?? r.selected_longitude)])]; const url = URL.createObjectURL(new Blob(['\uFEFF' + rows.map(row => row.map(escape).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' })); const a = document.createElement('a'); a.href = url; a.download = `munialert-${demo.value ? 'demo-' : ''}reports.csv`; a.click(); URL.revokeObjectURL(url); }
function expired() { if (!user.value) return; generation++; pendingView.value = null; user.value = null; notices.value = []; selected.value = null; modal.value = null; pendingReport.value = false; loading.value = false; view.value = 'overview'; }
async function completeSocial(result: SocialResult) {
  if (result.linked) { user.value = result.user; navigate('profile'); notify('Your sign-in account is connected.'); return; }
  pendingReport.value = result.intent === 'report';
  pendingView.value = ['profile', 'mine', 'activity', 'news', 'reports', 'map', 'guide'].includes(result.intent) ? result.intent as View : null;
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
  if ((status === 'success' || status === 'linked') && user.value) await completeSocial({ user: user.value, intent, linked: status === 'linked' });
  else { socialError.value = socialMessage(status === 'error' ? reason : 'expired'); modal.value = 'auth'; }
}
function restoreView(refreshData: unknown = true) {
  const query = new URLSearchParams(location.search);
  const next = query.has('news') ? 'news' : query.get('view') || 'overview';
  const allowed = navigation.value.some(item => item.id === next);
  const requiresLogin = ['mine', 'activity', 'profile'].includes(next);
  view.value = allowed && (!requiresLogin || user.value || demo.value) ? next as View : 'overview';
  mobileNav.value = false; page.value = 1;
  if (refreshData !== false) void refresh();
}
watch([search, category, status], () => { clearTimeout(filterTimer); page.value = 1; filterTimer = setTimeout(() => void refresh(), 350); });
onMounted(async () => { window.addEventListener('session-expired', expired); window.addEventListener('popstate', restoreView); window.addEventListener('social-authenticated', socialAuthenticated); await initializeSocialAuth(); if (!demo.value) { try { user.value = await api<User>('/api/auth/me'); } catch {} } restoreView(false); await restoreSocialReturn(); void loadMunicipalSettings().catch(() => {}); await refresh(); refreshTimer = setInterval(() => { if (!document.hidden && !modal.value && !selected.value && ['overview', 'map', 'reports', 'mine', 'activity'].includes(view.value)) void refresh(true); }, 60000); });
onBeforeUnmount(() => { clearInterval(refreshTimer); clearTimeout(filterTimer); clearTimeout(toastTimer); window.removeEventListener('session-expired', expired); window.removeEventListener('popstate', restoreView); window.removeEventListener('social-authenticated', socialAuthenticated); });

return { view,mobileNav,demo,user,reports,categories,departments,stats,notices,loading,online,error,search,category,status,page,lastPage,total,modal,selected,toast,pendingReport,socialIntent,synced,pushBusy,municipality,navigation,heading,filtered,urgent,unread,metricCards,municipalSettings,notify,refresh,navigate,startReport,cancelAuth,signedIn,logout,created,toggleDemo,push,markRead,openNotice,exportCsv };
}
