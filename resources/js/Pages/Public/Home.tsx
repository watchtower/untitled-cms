import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';

interface Slide {
    image: string;
    url?: string;
    sequence?: number;
    title?: string;
    subtitle?: string;
    caption?: string;
}

interface Banner {
    id: string;
    title: string;
    slides: Slide[];
}

interface Page {
    id: string;
    title: string;
    slug: string;
    seo_description?: string;
    featured_images?: string[];
    published_at: string;
}

interface Props {
    banners: Banner[];
    recentPages: Page[];
}

export default function Home({ banners, recentPages }: Props) {
    return (
        <PublicLayout>
            <Head title="Home" />

            {/* Hero / Banners Section */}
            {banners.length > 0 && (
                <div className="relative bg-gray-900">
                    <div className="relative max-w-7xl mx-auto">
                        <div className="relative h-96 w-full overflow-hidden">
                            {banners.flatMap((b, bi) => (b.slides || []).map((slide, si) => ({ slide, key: `${b.id}-${si}`, isFirst: bi === 0 && si === 0 }))).map(({ slide, key, isFirst }) => (
                                <div key={key} className={`absolute inset-0 transition-opacity duration-1000 ${isFirst ? 'opacity-100 z-10' : 'opacity-0 z-0'}`}>
                                    <img
                                        src={slide.image}
                                        alt={slide.title || "Banner"}
                                        className="h-full w-full object-cover"
                                    />
                                    <div className="absolute inset-0 bg-gray-900 bg-opacity-40 flex items-center justify-center">
                                        <div className="text-center">
                                            {slide.title && (
                                                <h2 className="text-4xl font-bold tracking-tight text-white sm:text-6xl">
                                                    {slide.title}
                                                </h2>
                                            )}
                                            {slide.subtitle && (
                                                <p className="mt-4 text-xl text-gray-300 max-w-2xl mx-auto">
                                                    {slide.subtitle}
                                                </p>
                                            )}
                                            {slide.caption && (
                                                <p className="mt-2 text-sm text-gray-400">
                                                    {slide.caption}
                                                </p>
                                            )}
                                            {slide.url && (
                                                <div className="mt-8">
                                                    <a
                                                        href={slide.url}
                                                        className="inline-block rounded-md border border-transparent bg-indigo-600 px-8 py-3 text-base font-medium text-white hover:bg-indigo-700"
                                                    >
                                                        Learn more
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* Recent Articles Section */}
            <div className="bg-white py-24 sm:py-32">
                <div className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Recent Articles</h2>
                        <p className="mt-2 text-lg leading-8 text-gray-600">
                            Stay up to date with our latest content.
                        </p>
                    </div>
                    <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        {recentPages.length > 0 ? (
                            recentPages.map((page) => (
                                <article key={page.id} className="flex flex-col items-start justify-between">
                                    <div className="relative w-full">
                                        {page.featured_images && page.featured_images.length > 0 ? (
                                            <img
                                                src={page.featured_images[0]}
                                                alt={page.title}
                                                className="aspect-[16/9] w-full rounded-2xl object-cover sm:aspect-[2/1] lg:aspect-[3/2]"
                                            />
                                        ) : (
                                            <div className="aspect-[16/9] w-full rounded-2xl bg-gray-100 object-cover sm:aspect-[2/1] lg:aspect-[3/2] flex items-center justify-center text-gray-400">
                                                <span>No Image</span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="max-w-xl">
                                        <div className="mt-8 flex items-center gap-x-4 text-xs">
                                            <time dateTime={page.published_at} className="text-gray-500">
                                                {new Date(page.published_at).toLocaleDateString()}
                                            </time>
                                        </div>
                                        <div className="group relative">
                                            <h3 className="mt-3 text-lg font-semibold leading-6 text-gray-900 group-hover:text-gray-600">
                                                <Link href={route('public.page', page.slug)}>
                                                    <span className="absolute inset-0" />
                                                    {page.title}
                                                </Link>
                                            </h3>
                                            <p className="mt-5 line-clamp-3 text-sm leading-6 text-gray-600">
                                                {page.seo_description || 'No description available.'}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="col-span-3 text-center text-gray-500">
                                No articles found.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
