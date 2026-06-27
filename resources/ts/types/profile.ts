export type ProfileClient = {
  name: string;
  first_surname: string;
  second_surname: string;
  gmail: string;
  provider: 'google' | 'local' | string;
  avatar_url: string | null;
};

export type ProfileFlash = {
  profileUpdated: boolean;
  passwordUpdated: boolean;
  passwordDefined: boolean;
  avatarUpdated: boolean;
};
