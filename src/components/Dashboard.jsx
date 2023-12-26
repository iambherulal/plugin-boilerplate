import React from 'react';
import {
	Card,
	CardContent,
	CardDescription,
	CardFooter,
	CardHeader,
	CardTitle,
} from './ui/card';
import PluginForm from './plugin-form';
import PluginPreview from './plugin-preview';
import Download from './download';
import Resources from './resources';

const Dashboard = () => {
	return (
		<div className="dashboard">
			<div className="mt-3 flex gap-8 max-w-5xl">
				<Card className="w-[420px]">
					<CardHeader>
						<CardTitle>Plugin Boilerplate Generator</CardTitle>
						<CardDescription>
							Enter Plugin Information
						</CardDescription>
					</CardHeader>
					<CardContent>
						<PluginForm />
					</CardContent>
				</Card>
				<div className="flex-1 flex flex-col gap-7">
					<Card>
						<CardHeader>
							<CardTitle>Preview</CardTitle>
						</CardHeader>
						<CardContent>
							<PluginPreview />
						</CardContent>
					</Card>
					<Card>
						<CardHeader>
							<CardTitle>Generated Plugin</CardTitle>
						</CardHeader>
						<CardContent>
							<Download />
						</CardContent>
					</Card>
					<Card>
						<CardHeader>
							<CardTitle>Resource</CardTitle>
						</CardHeader>
						<CardContent>
							<Resources />
						</CardContent>
					</Card>
				</div>
			</div>
		</div>
	);
};

export default Dashboard;
