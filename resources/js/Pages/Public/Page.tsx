import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";

interface Page {
    id: string;
    title: string;
    content: string;
    seo_title?: string;
    seo_description?: string;
    featured_images?: string[];
    published_at: string;
}

interface Props {
    page: Page;
}

export default function Page({ page }: Props) {
    const hasImages = page.featured_images && page.featured_images.length > 0;

    return (
        <PublicLayout>
            <Head>
                <title>{page.seo_title || page.title}</title>
                <meta name="description" content={page.seo_description || ''} />
            </Head>

            {hasImages && (
                <div className="w-full bg-muted/30 border-b">
                    {page.featured_images && page.featured_images.length > 1 ? (
                        <Carousel
                            plugins={[
                                Autoplay({
                                    delay: 5000,
                                }),
                            ]}
                            className="w-full"
                            opts={{
                                loop: true,
                            }}
                        >
                            <CarouselContent>
                                {(page.featured_images || []).map((image, index) => (
                                    <CarouselItem key={index}>
                                        <div className="w-full h-[250px] sm:h-[300px] md:h-[400px] relative lg:h-[450px]">
                                            <img
                                                src={image}
                                                alt={`${page.title} - Image ${index + 1}`}
                                                className="w-full h-full object-cover"
                                            />
                                        </div>
                                    </CarouselItem>
                                ))}
                            </CarouselContent>
                            <div className="hidden sm:block absolute inset-0 pointer-events-none">
                                <div className="max-w-7xl mx-auto h-full relative">
                                    <CarouselPrevious className="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-auto h-12 w-12 border-2 bg-background/80 hover:bg-background text-foreground" />
                                    <CarouselNext className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-auto h-12 w-12 border-2 bg-background/80 hover:bg-background text-foreground" />
                                </div>
                            </div>
                        </Carousel>
                    ) : (
                        <div className="w-full h-[250px] sm:h-[300px] md:h-[400px] lg:h-[450px]">
                            <img
                                src={page.featured_images?.[0] || ''}
                                alt={page.title}
                                className="w-full h-full object-cover"
                            />
                        </div>
                    )}
                </div>
            )}

            <div className="bg-background py-16 sm:py-24">
                <div className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="mx-auto max-w-3xl">
                        <div className="text-center">
                            <p className="text-base font-semibold leading-7 text-indigo-600">
                                {new Date(page.published_at).toLocaleDateString()}
                            </p>
                            <h1 className="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                                {page.title}
                            </h1>
                        </div>

                        <div className="mt-10 max-w-2xl mx-auto prose prose-lg dark:prose-invert prose-indigo text-muted-foreground">
                            <div dangerouslySetInnerHTML={{ __html: page.content }} />
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
