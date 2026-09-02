<script setup lang="ts" generic="TValue extends string | number">
import type { Component } from 'vue'
import { computed } from 'vue'
import { Check, PlusCircle } from 'lucide-vue-next'
import { cn } from '@lib/utils'
import { Badge } from '@/components/shadcn/badge'
import { Button } from '@/components/shadcn/button'
import {
	Command,
	CommandEmpty,
	CommandGroup,
	CommandInput,
	CommandItem,
	CommandList,
	CommandSeparator,
} from '@/components/shadcn/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover'
import { Separator } from '@/components/shadcn/separator'

interface FacetedFilterOption {
	label: string
	value: TValue
	icon?: Component
}

interface Props {
	title?: string
	options: FacetedFilterOption[]
	facets?: Map<TValue, number>
	emptyText?: string
}

const props = withDefaults(defineProps<Props>(), {
	emptyText: 'No results found.',
})

const selected = defineModel<TValue[]>({ default: () => [] })

const selectedSet = computed(() => new Set(selected.value))

function toggle(value: TValue) {
	const next = new Set(selectedSet.value)
	if (next.has(value)) {
		next.delete(value)
	}
	else {
		next.add(value)
	}
	selected.value = props.options
		.filter(option => next.has(option.value))
		.map(option => option.value)
}
</script>

<template>
	<Popover>
		<PopoverTrigger as-child>
			<Button variant="outline" size="sm" class="h-full border-dashed">
				<PlusCircle />
				{{ title }}
				<template v-if="selected.length > 0">
					<Separator orientation="vertical" class="mx-2 h-4" />
					<Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">
						{{ selected.length }}
					</Badge>
					<div class="hidden gap-1 lg:flex">
						<Badge v-if="selected.length > 2" variant="secondary" class="rounded-sm px-1 font-normal">
							{{ selected.length }} selected
						</Badge>
						<template v-else>
							<Badge v-for="option in options.filter(o => selectedSet.has(o.value))"
								:key="String(option.value)" variant="secondary" class="rounded-sm px-1 font-normal">
								{{ option.label }}
							</Badge>
						</template>
					</div>
				</template>
			</Button>
		</PopoverTrigger>
		<PopoverContent class="w-50 p-0" align="start">
			<Command>
				<CommandInput :placeholder="title" />
				<CommandList>
					<CommandEmpty>{{ emptyText }}</CommandEmpty>
					<CommandGroup>
						<CommandItem v-for="option in options" :key="String(option.value)" :value="String(option.value)"
							@select="toggle(option.value)">
							<div :class="cn(
								'flex size-4 items-center justify-center rounded-sm border border-primary',
								selectedSet.has(option.value)
									? 'bg-primary text-primary-foreground'
									: 'opacity-50 [&_svg]:invisible',
							)">
								<Check class="size-3.5 text-primary-foreground" />
							</div>
							<component :is="option.icon" v-if="option.icon" class="text-muted-foreground" />
							<span>{{ option.label }}</span>
							<span v-if="facets?.get(option.value)"
								class="ml-auto flex size-4 items-center justify-center font-mono text-xs">
								{{ facets.get(option.value) }}
							</span>
						</CommandItem>
					</CommandGroup>
					<template v-if="selected.length > 0">
						<CommandSeparator />
						<CommandGroup>
							<CommandItem :value="'__clear__'" class="justify-center text-center"
								@select="selected = []">
								Clear filters
							</CommandItem>
						</CommandGroup>
					</template>
				</CommandList>
			</Command>
		</PopoverContent>
	</Popover>
</template>
