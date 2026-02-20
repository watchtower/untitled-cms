import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';

interface Page {
    id: string;
    title: string;
    content: string;
    seo_title?: string;
    seo_description?: string;
    published_at: string;
}

interface Props {
    page: Page;
}

export default function Page({ page }: Props) {
    return (
        <PublicLayout>
            <Head>
                <title>{page.seo_title || page.title}</title>
                <meta name="description" content={page.seo_description || ''} />
            </Head>

            <div className="bg-white py-16 sm:py-24">
                <div className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="mx-auto max-w-3xl">
                        <div className="text-center">
                            <p className="text-base font-semibold leading-7 text-indigo-600">
                                {new Date(page.published_at).toLocaleDateString()}
                            </p>
                            <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                                {page.title}
                            </h1>
                        </div>

                        <div className="mt-10 max-w-2xl mx-auto prose prose-lg prose-indigo text-gray-700">
                            <div dangerouslySetInnerHTML={{ __html: page.content }} />
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
