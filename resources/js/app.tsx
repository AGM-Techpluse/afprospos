import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { ToastProvider } from './hooks/useToast'
import { ConfirmProvider } from './hooks/useConfirm'

type PageModule = { default: React.ComponentType }

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx', { eager: true })

createInertiaApp({
    title: (title) => title ? `${title} - AfProsPos` : 'AfProsPos',
    resolve: (name) => {
        const page = pages[`./Pages/${name}.tsx`]
        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }
        return page
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ToastProvider>
                <ConfirmProvider>
                    <App {...props} />
                </ConfirmProvider>
            </ToastProvider>
        )
    },
})

