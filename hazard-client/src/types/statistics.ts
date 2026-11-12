export type StatisticsGroup = 'day' | 'week' | 'month' | 'year';
export type StatisticsPeriod = { period: string; total: number; known_time: number; accidents: number; categories: Record<string, number> };
export type IncidentStatistics = {
  from: string; to: string; group: StatisticsGroup; basis: 'submitted' | 'incident'; timezone: string; category: string | null;
  total: number; known_time: number; date_basis: string;
  categories: { id: string; name: string; total: number }[]; periods: StatisticsPeriod[];
};
