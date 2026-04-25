import { InjectionKey } from 'vue';

export const ProjectPolicyKey: InjectionKey<App.Data.ProjectRole.ConfigData | null> = Symbol('project-policy');
