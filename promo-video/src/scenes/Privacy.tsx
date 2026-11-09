import { Audio } from '@remotion/media';
import { CanvasImage, Interactive, staticFile, useVideoConfig, type InteractivitySchema } from 'remotion';
import { FeatureCopy, Phone, Surface } from '../Visuals';
type Props = { title: string; description: string };
const PrivacyInner = ({ title, description }: Props) => { const { fps } = useVideoConfig(); return <Surface dark><FeatureCopy dark kicker="02 / Responsible evidence" title={title} description={description} /><Phone><CanvasImage name="Private evidence instructions" src={staticFile('screens/privacy.png')} premountFor={fps} style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'top' }} /></Phone><Audio name="Privacy narration" src={staticFile('audio/03-privacy.wav')} from={0.6 * fps} premountFor={fps} volume={0.95} /></Surface>; };
const schema = { title: { type: 'text-content', default: 'Private details.\nReviewed updates.', description: 'Headline' }, description: { type: 'text-content', default: 'Original evidence stays private.\nPublic summaries are separate.', description: 'Supporting text' } } as const satisfies InteractivitySchema;
export const Privacy = Interactive.withSchema({ Component: PrivacyInner, componentName: '<Privacy>', schema, wrapInSequence: true, layout: 'absolute-fill' });
