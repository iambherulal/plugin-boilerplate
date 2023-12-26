import React from 'react';
import usePluginStore from '../hooks/plugin-store';

export default function PluginPreview() {
	const { pluginData } = usePluginStore();
	return (
		<div className="flex">
			<div className="w-48">
				<div className="text-black mb-1 text-sm font-medium">
					{ pluginData?.name }
				</div>
				<div className="cursor-pointer">
					<span className="text-blue-600">Activate</span>{ ' ' }
					<span className="text-slate-400">|</span>{ ' ' }
					<span className="text-red-600">Delete</span>
				</div>
			</div>
			<div className="flex-1">
				<p className="text-[#2c3338] mb-2">
					{ pluginData?.description }
				</p>
				<div>
					Version 1.0.0 | By{ ' ' }
					<a
						target="_blank"
						className="cursor-pointer text-blue-600"
						href={ `${ pluginData?.authorUrl }` }
					>
						{ pluginData?.authorName }
					</a>{ ' ' }
					|{ ' ' }
					<a
						className="cursor-pointer text-blue-600"
						target="_blank"
						href={ `${ pluginData?.url }` }
					>
						Visit plugin site
					</a>
				</div>
			</div>
		</div>
	);
}
