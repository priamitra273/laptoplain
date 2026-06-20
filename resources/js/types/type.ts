import { InjectionKey, Ref } from 'vue';

export const ProjectPolicyKey: InjectionKey<App.Data.ProjectRole.ConfigData | null> = Symbol('project-policy');

export const LiveProjectProgressKey: InjectionKey<Ref<number>> = Symbol('live-project-progress');
