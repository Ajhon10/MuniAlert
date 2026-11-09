import { Audio } from '@remotion/media';
import { CanvasImage, Interactive, staticFile, useVideoConfig, type InteractivitySchema } from 'remotion';
import { FeatureCopy, Phone, Surface } from '../Visuals';
type Props = { title: string; description: string };
const RecordsInner = ({ title, description }: Props) => { const { fps } = useVideoConfig(); return <Surface dark><FeatureCopy dark kicker="04 / Clearer incident records" title={title} description={description} /><Phone><CanvasImage name="Weekly record summary interface" src={staticFile('screens/records.png')} premountFor={fps} style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'top' }} /></Phone><Audio name="Records narration" src={staticFile('audio/05-records.wav')} from={0.6 * fps} premountFor={fps} volume={0.95} /></Surface>; };
const schema = { title: { type: 'text-content', default: 'Weeks. Months.\nClearer records.', description: 'Headline' }, description: { type: 'text-content', default: 'Filter records. Export a summary.\nReport counts, clearly labeled.', description: 'Supporting text' } } as const satisfies InteractivitySchema;
export const Records = Interactive.withSchema({ Component: RecordsInner, componentName: '<Records>', schema, wrapInSequence: true, layout: 'absolute-fill' });
