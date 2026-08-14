/**
 * The wizard's optional additional user.
 *
 * The product brief asked for "create the admin account at first install". That is not
 * possible: WordPress creates the administrator during core installation, long before a theme
 * or plugin exists to ask. So this creates an *additional* user with one of the product's own
 * roles, which is the useful half of the original request.
 */

export type WizardRole = 'edulume_site_manager' | 'edulume_content_editor' | 'edulume_counsellor';

export interface RoleChoice {
  readonly role: WizardRole;
  readonly label: string;
  readonly summary: string;
}

export const WIZARD_ROLES: readonly RoleChoice[] = [
  {
    role: 'edulume_site_manager',
    label: 'Site Manager',
    summary: 'Everything except installing plugins and editing code.',
  },
  {
    role: 'edulume_content_editor',
    label: 'Content Editor',
    summary: 'Writes and publishes content. Cannot change theme settings or see leads.',
  },
  {
    role: 'edulume_counsellor',
    label: 'Counsellor',
    summary: 'Sees only the leads assigned to them. No content or settings access.',
  },
];

export type PasswordStrength = 'weak' | 'fair' | 'strong' | 'excellent';

export interface PasswordAssessment {
  readonly strength: PasswordStrength;
  /** 0–4, so a meter can render without knowing the labels. */
  readonly score: number;
  /** What to change, phrased as an instruction rather than a complaint. */
  readonly advice: readonly string[];
}

const MIN_LENGTH = 12;

/**
 * A deliberately transparent strength estimate.
 *
 * Not an entropy model: a person needs to know *what to add*, and a score that moves when they
 * add a symbol teaches that in a way "37 bits" does not. The real defence is the length floor
 * below, which the submit path enforces regardless of what the meter says.
 */
export function assessPassword(password: string): PasswordAssessment {
  const advice: string[] = [];
  let score = 0;

  if (password.length >= MIN_LENGTH) {
    score += 1;
  } else {
    advice.push(`Use at least ${MIN_LENGTH} characters.`);
  }

  if (password.length >= 16) {
    score += 1;
  }

  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
    score += 1;
  } else {
    advice.push('Mix upper and lower case.');
  }

  if (/\d/.test(password)) {
    score += 1;
  } else {
    advice.push('Add a number.');
  }

  if (/[^A-Za-z0-9]/.test(password)) {
    score += 1;
  } else {
    advice.push('Add a symbol.');
  }

  const capped = Math.min(score, 4);
  const strength: PasswordStrength =
    capped >= 4 ? 'excellent' : capped === 3 ? 'strong' : capped === 2 ? 'fair' : 'weak';

  return { strength, score: capped, advice };
}

const GENERATED_ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*-_=+';

/**
 * Generates a password from an injected random source.
 *
 * The source is a parameter so the generator is testable and so production can pass
 * `crypto.getRandomValues` rather than `Math.random`, which is not suitable for credentials.
 */
export function generatePassword(randomBytes: (length: number) => Uint8Array, length = 20): string {
  const bytes = randomBytes(length);
  let password = '';

  for (let index = 0; index < length; index += 1) {
    const byte = bytes[index] ?? 0;

    password += GENERATED_ALPHABET[byte % GENERATED_ALPHABET.length];
  }

  return password;
}

export interface NewUserDraft {
  readonly username: string;
  readonly email: string;
  readonly password: string;
  readonly role: WizardRole;
}

export interface FieldError {
  readonly field: 'username' | 'email' | 'password' | 'role';
  readonly message: string;
}

/** Availability comes from the server; the shape is here so validation can be tested offline. */
export interface Availability {
  readonly usernameTaken: boolean;
  readonly emailTaken: boolean;
}

const USERNAME = /^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i;
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

/**
 * Validates the draft, including uniqueness.
 *
 * Uniqueness is checked here and shown inline rather than being discovered on submit, because
 * a wizard that loses a filled form to "username already exists" is a wizard people abandon.
 */
export function validateNewUser(
  draft: NewUserDraft,
  availability: Availability
): readonly FieldError[] {
  const errors: FieldError[] = [];

  if (draft.username.trim() === '') {
    errors.push({ field: 'username', message: 'Choose a username.' });
  } else if (!USERNAME.test(draft.username)) {
    errors.push({
      field: 'username',
      message:
        'Use letters, numbers, dots, dashes and underscores, starting and ending with a letter or number.',
    });
  } else if (availability.usernameTaken) {
    errors.push({ field: 'username', message: 'That username is already in use.' });
  }

  if (!EMAIL.test(draft.email)) {
    errors.push({ field: 'email', message: 'Enter a valid email address.' });
  } else if (availability.emailTaken) {
    errors.push({ field: 'email', message: 'That email address already has an account.' });
  }

  if (draft.password.length < MIN_LENGTH) {
    errors.push({ field: 'password', message: `Use at least ${MIN_LENGTH} characters.` });
  }

  if (!WIZARD_ROLES.some((choice) => choice.role === draft.role)) {
    errors.push({ field: 'role', message: 'Choose a role.' });
  }

  return errors;
}

export function canSubmit(draft: NewUserDraft, availability: Availability): boolean {
  return validateNewUser(draft, availability).length === 0;
}
