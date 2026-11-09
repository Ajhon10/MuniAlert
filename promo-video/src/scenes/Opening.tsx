import { Audio } from '@remotion/media';
import { CanvasImage, Interactive, staticFile, useVideoConfig, type InteractivitySchema } from 'remotion';
import { FeatureCopy, Phone, Surface } from '../Visuals';
type Props = { title: string; description: string };
const OpeningInner = ({ title, description }: Props) => { const { fps } = useVideoConfig(); return <Surface><FeatureCopy kicker="A clearer municipal reporting experience" title={title} description={description} /><Phone><CanvasImage name="Finished home screen" src={staticFile('screens/home.png')} premountFor={fps} style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'top' }} /></Phone><Audio name="Opening narration" src={staticFile('audio/01-intro.wav')} from={0.6 * fps} premountFor={fps} volume={0.95} /></Surface>; };
const schema = { title: { type: 'text-content', default: 'See it.\nReport it.', description: 'Headline' }, description: { type: 'text-content', default: 'Municipal emergencies.\nPublic hazards. Clear records.', description: 'Supporting text' } } as const satisfies InteractivitySchema;
export const Opening = Interactive.withSchema({ Component: OpeningInner, componentName: '<Opening>', schema, wrapInSequence: true, layout: 'absolute-fill' });
