import type { ReactNode } from 'react';
import { AbsoluteFill, Easing, Interactive, interpolate, useCurrentFrame, useVideoConfig } from 'remotion';

export const Mark = ({ light = false, size = 74 }: { light?: boolean; size?: number }) => (
  <svg width={size} height={size} viewBox="0 0 80 80" fill="none" aria-hidden="true">
    <path d="M40 6 66 17v21c0 17-10 29-26 36C24 67 14 55 14 38V17L40 6Z" fill={light ? '#c7edcc' : '#215b42'} />
    <path d="m28 40 8 8 17-19" stroke={light ? '#153e2e' : '#f7f6ec'} strokeWidth="6" strokeLinecap="round" strokeLinejoin="round" />
  </svg>
);

export const Wordmark = ({ light = false }: { light?: boolean }) => (
  <div style={{ display: 'flex', alignItems: 'center', gap: 22 }}><Mark light={light} /><span style={{ fontSize: 66, fontWeight: 750, letterSpacing: -3, color: light ? '#f5f4e9' : '#183d2e' }}>MuniAlert<span style={{ color: '#83be8e' }}>.</span></span></div>
);

export const Surface = ({ children, dark = false }: { children: ReactNode; dark?: boolean }) => {
  const frame = useCurrentFrame(); const { fps, width, height } = useVideoConfig();
  return <AbsoluteFill style={{ background: dark ? '#123b2c' : '#f5f3e9', color: dark ? '#f7f5e9' : '#183d2e', fontFamily: 'Segoe UI, Arial, sans-serif', overflow: 'hidden' }}>
    <Interactive.Div name="Ambient circle" style={{ position: 'absolute', width: width * 0.9, height: width * 0.9, left: width * 0.55, top: height * 0.1, borderRadius: '50%', border: dark ? '2px solid #3c6c4f' : '2px solid #d6dfcb', opacity: 0.7, scale: interpolate(frame, [0, 10 * fps], [0.96, 1.06], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) }} />
    <div style={{ position: 'absolute', inset: 0, backgroundImage: dark ? 'radial-gradient(#b6ddc518 1px, transparent 1px)' : 'radial-gradient(#526b4425 1px, transparent 1px)', backgroundSize: '34px 34px' }} />
    {children}
    <div style={{ position: 'absolute', left: width > height ? 120 : 88, right: width > height ? 120 : 88, bottom: 48, display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 20, fontSize: width > height ? 25 : 26, color: dark ? '#bcd8c3' : '#48634d', letterSpacing: 0.3 }}><span>MUNI ALERT / PRODUCT PREVIEW</span><span>Demonstration data</span></div>
  </AbsoluteFill>;
};

export const Phone = ({ children }: { children: ReactNode }) => {
  const frame = useCurrentFrame(); const { width, height, fps } = useVideoConfig();
  return <Interactive.Div name="Android interface" style={{ position: 'absolute', left: width > height ? 1300 : 243, top: width > height ? 100 : 670, width: width > height ? 470 : 594, height: width > height ? 880 : 1112, padding: 14, borderRadius: 52, background: '#18382d', border: '2px solid #73997c', boxShadow: '0 38px 80px #0b261d38, inset 0 0 0 3px #39644c', overflow: 'hidden', translate: interpolate(frame, [0, 0.8 * fps], ['0px 60px', '0px 0px'], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.bezier(0.16, 1, 0.3, 1) }), scale: interpolate(frame, [0, 9 * fps], [0.96, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) }}>
    <div style={{ position: 'relative', width: '100%', height: '100%', background: '#f6faf8', borderRadius: 38, overflow: 'hidden' }}>{children}</div>
  </Interactive.Div>;
};

export const FeatureCopy = ({ kicker, title, description, dark = false }: { kicker: string; title: string; description: string; dark?: boolean }) => {
  const frame = useCurrentFrame(); const { width, height, fps } = useVideoConfig();
  return <>
    <div style={{ position: 'absolute', left: width > height ? 120 : 88, top: width > height ? 90 : 94 }}><Wordmark light={dark} /></div>
    <Interactive.Div name="Feature text" style={{ position: 'absolute', left: width > height ? 120 : 88, top: width > height ? 255 : 226, width: width > height ? 1040 : 904, opacity: interpolate(frame, [0, 0.55 * fps], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }), translate: interpolate(frame, [0, 0.7 * fps], ['0px 26px', '0px 0px'], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.bezier(0.16, 1, 0.3, 1) }) }}>
      <div style={{ color: dark ? '#bfe0b9' : '#4a7047', fontSize: width > height ? 27 : 26, fontWeight: 650, textTransform: 'uppercase', letterSpacing: 3.5, marginBottom: 20 }}>{kicker}</div>
      <div style={{ fontSize: width > height ? 136 : 96, fontWeight: 750, letterSpacing: width > height ? -6 : -4, lineHeight: 1.04, whiteSpace: 'pre-line', color: dark ? '#f5f5e9' : '#183d2e' }}>{title}</div>
      <div style={{ marginTop: width > height ? 36 : 22, maxWidth: width > height ? 900 : 900, fontSize: width > height ? 49 : 43, fontWeight: 400, lineHeight: 1.4, whiteSpace: 'pre-line', color: dark ? '#d2e5cf' : '#415b43' }}>{description}</div>
    </Interactive.Div>
  </>;
};
