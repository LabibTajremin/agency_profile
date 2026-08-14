export { REST_NAMESPACE, restPath } from './restPath';
export { SettingsSearchIndex } from './settingsSearch';
export type { RegisteredControl, SearchHit } from './settingsSearch';

export { PANELS, allControls, panelById } from './panels/catalogue';
export { advancedControls, basicControls, clearPath, readPath, writePath } from './panels/controls';
export type {
  ControlDefinition,
  ControlKind,
  PanelDefinition,
  SampleKind,
} from './panels/controls';
export { ConfiguratorSession } from './panels/panelState';
export type { ControlOrigin, ControlState, SettingsSnapshot } from './panels/panelState';

export { RestError, SettingsClient } from './settingsClient';
export type { RestRequest, RestResponse, RestTransport } from './settingsClient';

export {
  DEVICE_FRAMES,
  PREVIEW_MESSAGE,
  PreviewBridge,
  applyPreviewCommand,
  diffTokens,
  isEmptyPatch,
  parsePreviewMessage,
  unsavedChangesPrompt,
} from './preview/previewBridge';
export type {
  DeviceFrame,
  PreviewCommand,
  PreviewDevice,
  PreviewMessage,
  StyleTarget,
} from './preview/previewBridge';

export {
  INITIAL_PROGRESS,
  WIZARD_STEPS,
  checklist,
  completeStep,
  finish,
  nextStep,
  previousStep,
  restart,
  skipStep,
} from './wizard/wizardFlow';
export type { ChecklistItem, WizardProgress, WizardStep, WizardStepId } from './wizard/wizardFlow';

export {
  WIZARD_ROLES,
  assessPassword,
  canSubmit,
  generatePassword,
  validateNewUser,
} from './wizard/newUser';
export type {
  Availability,
  FieldError,
  NewUserDraft,
  PasswordAssessment,
  PasswordStrength,
  RoleChoice,
  WizardRole,
} from './wizard/newUser';
