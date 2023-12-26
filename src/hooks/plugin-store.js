import { create } from 'zustand';

// Create a zustand store
const usePluginStore = create( ( set ) => ( {
	pluginData: {
		name: 'My Basics Plugin',
		slug: 'basic-plugin',
		url: 'https://example.com/plugins/the-basics/',
		authorName: 'John Smith',
		authorUrl: 'https://author.example.com/',
		install: false,
		description:
			'A short description of the plugin, as displayed in the Plugins section in the WordPress Admin.',
	},
	setPluginData: ( newData ) => set( { pluginData: { ...newData } } ),
} ) );

export default usePluginStore;
