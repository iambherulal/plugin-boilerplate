import { cva } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '../../lib/utils';

const toastVariants = cva(
	'flex w-full rounded-md text-sm border px-3 py-2 my-3 toast',
	{
		variants: {
			variant: {
				default: 'text-green-800 border-green-300 bg-green-50',
				info: 'text-blue-800 border-blue-300 bg-blue-50',
				success: 'text-green-800 border-green-300 bg-green-50',
				warning: 'text-yellow-800 border-yellow-300 bg-yellow-50',
				error: 'text-red-800 border-red-300 bg-red-50',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	}
);

const Toast = React.forwardRef(
	( { className, variant, message, ...props }, ref ) => {
		return (
			<div
				className={ cn(
					toastVariants( { variant, className }, 'toast' )
				) }
				ref={ ref }
				{ ...props }
				dangerouslySetInnerHTML={ { __html: message } }
			/>
		);
	}
);
Toast.displayName = 'Toast';

export { Toast, toastVariants };
