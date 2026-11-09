import { Composition, Folder, Freeze, Still } from 'remotion';
import { Promo } from './Promo';
import { Opening } from './scenes/Opening';
import { Reporting } from './scenes/Reporting';
import { Privacy } from './scenes/Privacy';
import { Tracking } from './scenes/Tracking';
import { Records } from './scenes/Records';
import { Guide } from './scenes/Guide';
import { Closing } from './scenes/Closing';
const Poster = () => <Freeze frame={100}><Opening title={'See it.\nReport it.'} description={'Municipal emergencies.\nPublic hazards. Clear records.'} /></Freeze>;

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition id="MuniAlertPortrait" component={Promo} width={1080} height={1920} fps={30} durationInFrames={1800} />
      <Composition id="MuniAlertLandscape" component={Promo} width={1920} height={1080} fps={30} durationInFrames={1800} />
      <Still id="MuniAlertPoster" component={Poster} width={1080} height={1920} />
      <Folder name="Editable-scenes">
        <Composition id="Opening" component={Opening} width={1080} height={1920} fps={30} durationInFrames={210} defaultProps={{ title: 'See it.\nReport it.', description: 'Municipal emergencies.\nPublic hazards. Clear records.' }} />
        <Composition id="Reporting" component={Reporting} width={1080} height={1920} fps={30} durationInFrames={330} defaultProps={{ title: 'A photo.\nA clear location.', description: 'Describe what happened.\nConfirm the incident pin.' }} />
        <Composition id="Privacy" component={Privacy} width={1080} height={1920} fps={30} durationInFrames={240} defaultProps={{ title: 'Private details.\nReviewed updates.', description: 'Original evidence stays private.\nPublic summaries are separate.' }} />
        <Composition id="Tracking" component={Tracking} width={1080} height={1920} fps={30} durationInFrames={330} defaultProps={{ title: 'From report\nto resolution.', description: 'Review. Assign. Document action.\nFollow each saved update.' }} />
        <Composition id="Records" component={Records} width={1080} height={1920} fps={30} durationInFrames={270} defaultProps={{ title: 'Weeks. Months.\nClearer records.', description: 'Filter records. Export a summary.\nReport counts, clearly labeled.' }} />
        <Composition id="Guide" component={Guide} width={1080} height={1920} fps={30} durationInFrames={300} defaultProps={{ title: 'A guide\nat every step.', description: 'Take the highlighted tour.\nSearch the user manual.' }} />
        <Composition id="Closing" component={Closing} width={1080} height={1920} fps={30} durationInFrames={210} defaultProps={{ title: 'Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking', description: 'Immediate danger? Call emergency services first.' }} />
      </Folder>
    </>
  );
};
