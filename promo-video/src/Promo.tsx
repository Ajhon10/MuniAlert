import { Audio } from '@remotion/media';
import { TransitionSeries, linearTiming } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';
import { interpolate, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { Opening } from './scenes/Opening';
import { Reporting } from './scenes/Reporting';
import { Privacy } from './scenes/Privacy';
import { Tracking } from './scenes/Tracking';
import { Records } from './scenes/Records';
import { Guide } from './scenes/Guide';
import { Closing } from './scenes/Closing';

export const Promo = () => {
  const frame = useCurrentFrame(); const { fps } = useVideoConfig();
  return <>
    <TransitionSeries>
      <TransitionSeries.Sequence name="Meet MuniAlert" durationInFrames={7 * fps} premountFor={fps}><Opening title={'See it.\nReport it.'} description={'Municipal emergencies.\nPublic hazards. Clear records.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="Photo and location reporting" durationInFrames={11 * fps} premountFor={fps}><Reporting title={'A photo.\nA clear location.'} description={'Describe what happened.\nConfirm the incident pin.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="Private evidence" durationInFrames={8 * fps} premountFor={fps}><Privacy title={'Private details.\nReviewed updates.'} description={'Original evidence stays private.\nPublic summaries are separate.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="From report to resolution" durationInFrames={11 * fps} premountFor={fps}><Tracking title={'From report\nto resolution.'} description={'Review. Assign. Document action.\nFollow each saved update.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="Weekly and monthly records" durationInFrames={9 * fps} premountFor={fps}><Records title={'Weeks. Months.\nClearer records.'} description={'Filter records. Export a summary.\nReport counts, clearly labeled.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="Built-in guide and manual" durationInFrames={10 * fps} premountFor={fps}><Guide title={'A guide\nat every step.'} description={'Take the highlighted tour.\nSearch the user manual.'} /></TransitionSeries.Sequence>
      <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: 0.5 * fps })} />
      <TransitionSeries.Sequence name="Capstone title and safety" durationInFrames={7 * fps} premountFor={fps}><Closing title="Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking" description="Immediate danger? Call emergency services first." /></TransitionSeries.Sequence>
    </TransitionSeries>
    <Audio name="Original instrumental soundtrack" src={staticFile('audio/original-music.wav')} premountFor={fps} volume={interpolate(frame, [0, 1.2 * fps, 58 * fps, 60 * fps], [0, 0.23, 0.23, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' })} />
  </>;
};
