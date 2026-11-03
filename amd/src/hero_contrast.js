/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
/** Derive safe contrast treatments from displayed Hero and page-header media. */
const luminance = (context, width, height) => {
    const pixels = context.getImageData(0, 0, width, height).data;
    let total = 0;
    let samples = 0;
    for (let index = 0; index < pixels.length; index += 64) {
        total += (.2126 * pixels[index]) + (.7152 * pixels[index + 1]) + (.0722 * pixels[index + 2]);
        samples++;
    }
    return samples ? total / samples : 128;
};

const analyse = (element, source, datasetKey = 'frontpageMediaTone') => {
    try {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 18;
        const context = canvas.getContext('2d', {willReadFrequently: true});
        context.drawImage(source, 0, 0, canvas.width, canvas.height);
        element.dataset[datasetKey] = luminance(context, canvas.width, canvas.height) > 145 ? 'light' : 'dark';
    } catch (error) {
        element.dataset[datasetKey] = 'unknown';
    }
};

const initialiseHero = hero => {
    if (!hero.classList.contains('local-frontpage-hero-contrast-auto')) {
        return;
    }
    const value = getComputedStyle(hero).getPropertyValue('--local-frontpage-hero-background-image');
    const match = value.match(/^\s*url\(["']?(.*?)["']?\)\s*$/);
    if (!match) {
        return;
    }
    const image = new Image();
    image.onload = () => analyse(hero, image);
    image.src = match[1];
};

export const init = () => {
    document.querySelectorAll('.local-frontpage-hero').forEach(initialiseHero);
};
