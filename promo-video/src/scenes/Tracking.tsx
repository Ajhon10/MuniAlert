import { Audio } from '@remotion/media';
import { CanvasImage, Interactive, staticFile, useVideoConfig, type InteractivitySchema } from 'remotion';
import { FeatureCopy, Phone, Surface } from '../Visuals';
type Props = { title: string; description: string };
const TrackingInner = ({ title, description }: Props) => { const { fps } = useVideoConfig(); return <Surface><FeatureCopy kicker="03 / Municipal coordination" title={title} description={description} /><Phone><CanvasImage name="Actual report timeline interface" src={staticFile('screens/tracking.png')} premountFor={fps} style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'top' }} /></Phone><Audio name="Tracking narration" src={staticFile('audio/04-tracking.wav')} from={0.6 * fps} premountFor={fps} volume={0.95} /></Surface>; };
const schema = { title: { type: 'text-content', default: 'From report\nto resolution.', description: 'Headline' }, description: { type: 'text-content', default: 'Review. Assign. Document action.\nFollow each saved update.', description: 'Supporting text' } } as const satisfies InteractivitySchema;
export const Tracking = Interactive.withSchema({ Component: TrackingInner, componentName: '<Tracking>', schema, wrapInSequence: true, layout: 'absolute-fill' });
