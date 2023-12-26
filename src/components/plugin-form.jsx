import { zodResolver } from '@hookform/resolvers/zod';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';
import usePluginStore from '../hooks/plugin-store';
import { wpdata } from "../lib/utils";
import { Button } from './ui/button';
import { Checkbox } from './ui/checkbox';
import {
	Form,
	FormControl,
	FormField,
	FormItem,
	FormLabel,
	FormMessage
} from './ui/form';
import { Input } from './ui/input';
import { Textarea } from './ui/textarea';
import { Toast } from './ui/toast';

const slugSchema = z.string().refine(
	(slug) => {
		const slugRegex = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
		return slugRegex.test(slug);
	},
	{
		message: 'Invalid slug format.',
	}
);

const formSchema = z.object({
	name: z
		.string()
		.min(2, 'Plugin name must contain at least 2 character(s)')
		.max(50),
	slug: slugSchema,
	url: z.string().url(),
	authorUrl: z.string().url(),
	authorName: z
		.string()
		.min(5, 'Author name must contain at least 5 character(s)')
		.max(50),
	description: z
		.string()
		.min(5, 'Plugin descriptio must contain at least 5 character(s)')
		.max(140),
	install: z.boolean().default(false).optional(),
});

export default function PluginForm() {
	const [buttonText, setButtonText] = React.useState('Download');
	const [loading, setLoading] = React.useState(false);
	const [toast, setToast] = React.useState(false);
	const { pluginData } = usePluginStore();
	const { nonce, rest_url } = wpdata;

	const form = useForm({
		resolver: zodResolver(formSchema),
		// defaultValues: usePluginStore.getState().pluginData,
	});

	useEffect(() => {
		form.watch((data) => {
			usePluginStore.setState({ pluginData: data });
		});
	}, [form]);

	async function onSubmit(values) {
		setLoading(true);

		try {
			const response = await axios.post(
				`${rest_url}/create`,
				values,
				{
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
				}
			);
			const { data } = response.data;

			setLoading(false);

			if (!pluginData?.install) {
				const link = document.createElement('a');
				link.href = data;
				link.download = `${values.slug}.zip`;
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
			}
			setToast({
				variant: 'success',
				message: pluginData?.install
					? `Plugin installed successfully. <a htef="/wp-admin/plugins.php">Visit plugin</a>`
					: 'Plugin downloaded successfully.',
			});
		} catch (error) {
			console.error('Error:', error);
			setLoading(false);
			setToast({
				variant: 'error',
				message: 'Something went wrong. Please try again.',
			});
		}
	}

	return (
		<Form {...form}>
			<form
				onSubmit={form.handleSubmit(onSubmit)}
				className="space-y-4"
			>
				<FormField
					control={form.control}
					name="name"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Plugin Name</FormLabel>
							<FormControl>
								<Input
									placeholder="My Basics Plugin"
									{...field}
								/>
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="slug"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Plugin Slug</FormLabel>
							<FormControl>
								<Input
									placeholder="basic-plugin"
									{...field}
								/>
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="url"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Plugin URL</FormLabel>
							<FormControl>
								<Input
									placeholder="https://example.com/plugins/the-basics/"
									{...field}
								/>
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="authorName"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Author Name</FormLabel>
							<FormControl>
								<Input placeholder="John Smith" {...field} />
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="authorUrl"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Author URL</FormLabel>
							<FormControl>
								<Input
									placeholder="https://author.example.com/"
									{...field}
								/>
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="description"
					render={({ field }) => (
						<FormItem>
							<FormLabel>Description</FormLabel>
							<FormControl>
								<Textarea
									placeholder="Plugin Short Description"
									{...field}
								/>
							</FormControl>
							<FormMessage />
						</FormItem>
					)}
				/>
				<FormField
					control={form.control}
					name="install"
					render={({ field }) => (
						<FormItem className="flex flex-row items-start space-x-3 space-y-0">
							<FormControl>
								<Checkbox
									checked={field.value}
									onCheckedChange={(checked) => {
										field.onChange(checked);
										setButtonText(
											checked ? 'Install' : 'Download'
										);
									}}
								/>
							</FormControl>
							<div className="space-y-1 leading-none">
								<FormLabel>
									I want to install this plugin.
								</FormLabel>
							</div>
						</FormItem>
					)}
				/>
				<Button disabled={loading} type="submit">
					{loading && (
						<Loader2 className="animate-spin mr-2" size={16} />
					)}
					{buttonText}
				</Button>
			</form>
			{toast && (
				<Toast variant={toast.variant} message={toast.message} />
			)}
		</Form>
	);
}
