/**
 * The eight-step setup wizard.
 *
 * Skippable, resumable and re-runnable are not three features — they are one requirement: the
 * wizard must never be the thing standing between a person and `wp-admin`. So progress is
 * stored per step, every step can be left incomplete, and finishing is a state, not a door that
 * locks behind you.
 */

export type WizardStepId =
  'welcome' | 'identity' | 'contact' | 'style' | 'content' | 'user' | 'essentials' | 'done';

export interface WizardStep {
  readonly id: WizardStepId;
  readonly title: string;
  readonly summary: string;
  /** False for steps that can be left entirely alone — most of them. */
  readonly isRequired: boolean;
}

export const WIZARD_STEPS: readonly WizardStep[] = [
  {
    id: 'welcome',
    title: 'Welcome',
    summary: 'What this wizard sets up, and your licence key if you have one.',
    isRequired: false,
  },
  {
    id: 'identity',
    title: 'Site identity',
    summary: 'Name, tagline, logo and favicon. The favicon generates the full icon set.',
    isRequired: false,
  },
  {
    id: 'contact',
    title: 'Contact and branches',
    summary: 'Phone, WhatsApp, email and your first office. More branches can be added later.',
    isRequired: false,
  },
  {
    id: 'style',
    title: 'Style',
    summary: 'Accent, typography and density, with the live preview beside them.',
    isRequired: false,
  },
  {
    id: 'content',
    title: 'Content',
    summary: 'Import a starter demo, import your own CSV, or start empty.',
    isRequired: false,
  },
  {
    id: 'user',
    title: 'Add a user',
    summary: 'Optional. Creates one extra Site Manager, Content Editor or Counsellor.',
    isRequired: false,
  },
  {
    id: 'essentials',
    title: 'Essentials',
    summary: 'Permalinks, time zone, the privacy page and the forms you want switched on.',
    isRequired: false,
  },
  {
    id: 'done',
    title: 'Done',
    summary: 'A checklist of what is set up and what is still empty.',
    isRequired: false,
  },
];

export interface WizardProgress {
  /** Steps the person has completed, in no particular order. */
  readonly completed: readonly WizardStepId[];
  readonly currentStep: WizardStepId;
  /** True once the person has reached the end or dismissed the wizard. */
  readonly isFinished: boolean;
}

export const INITIAL_PROGRESS: WizardProgress = {
  completed: [],
  currentStep: 'welcome',
  isFinished: false,
};

function indexOfStep(id: WizardStepId): number {
  return WIZARD_STEPS.findIndex((step) => step.id === id);
}

export function nextStep(id: WizardStepId): WizardStepId {
  const next = WIZARD_STEPS[indexOfStep(id) + 1];

  return next?.id ?? 'done';
}

export function previousStep(id: WizardStepId): WizardStepId {
  const index = indexOfStep(id);
  const previous = index <= 0 ? undefined : WIZARD_STEPS[index - 1];

  return previous?.id ?? 'welcome';
}

export function completeStep(progress: WizardProgress, id: WizardStepId): WizardProgress {
  const completed = progress.completed.includes(id)
    ? progress.completed
    : [...progress.completed, id];
  const following = nextStep(id);

  return {
    completed,
    currentStep: following,
    isFinished: progress.isFinished || (id === 'done' && following === 'done'),
  };
}

/** Skipping advances without marking the step done, so the final checklist still shows the gap. */
export function skipStep(progress: WizardProgress, id: WizardStepId): WizardProgress {
  return { ...progress, currentStep: nextStep(id) };
}

export function finish(progress: WizardProgress): WizardProgress {
  return { ...progress, currentStep: 'done', isFinished: true };
}

/** Re-running clears the finished flag but keeps what was already done, so nothing is redone blindly. */
export function restart(progress: WizardProgress): WizardProgress {
  return { ...progress, currentStep: 'welcome', isFinished: false };
}

export interface ChecklistItem {
  readonly step: WizardStepId;
  readonly title: string;
  readonly isComplete: boolean;
}

/** The closing screen: what is set up, and what was skipped and can still be done later. */
export function checklist(progress: WizardProgress): readonly ChecklistItem[] {
  return WIZARD_STEPS.filter((step) => step.id !== 'welcome' && step.id !== 'done').map((step) => ({
    step: step.id,
    title: step.title,
    isComplete: progress.completed.includes(step.id),
  }));
}
