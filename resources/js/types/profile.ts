export type ProfileClient = {
  name: string;
  first_surname: string;
  second_surname: string;
  gmail: string;
  provider: 'google' | 'local' | string;
};

export type ProfileFlash = {
  profileUpdated: boolean;
  passwordUpdated: boolean;
  passwordDefined: boolean;
};
