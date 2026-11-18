import { Extension } from '@tiptap/core';
import { Image } from '@tiptap/extension-image';
import type { Node as ProseNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';

// CMP-ADM-RTE for articles (FR-NEWS-001/005/006): the pieces Tiptap does not ship — a library image
// that keeps its asset id, alt text and caption, and the heading-order guard. The sentences are the
// same as the server's (App\Support\News\PostBody::problems), so what the editor warns about is what
// a save would refuse.

export type DocIssue = { kind: 'heading' | 'image'; message: string };

/** FR-NEWS-005 + AC-NEWS-02, in document order. */
export function documentIssues(doc: ProseNode): DocIssue[] {
    const issues: DocIssue[] = [];
    let previous = 1;
    let image = 0;

    doc.descendants((node) => {
        if (node.type.name === 'heading') {
            const level = Number(node.attrs.level);

            if (level > previous + 1) {
                const text = node.textContent.trim().slice(0, 40);

                issues.push({
                    kind: 'heading',
                    message: `Heading ${level} “${text}” needs a Heading ${level - 1} before it.`,
                });
            }

            previous = level;

            return false;
        }

        if (node.type.name === 'image') {
            image += 1;

            if (
                !node.attrs.decorative &&
                String(node.attrs.alt ?? '').trim() === ''
            ) {
                issues.push({
                    kind: 'image',
                    message: `Image ${image} needs alt text or must be marked decorative.`,
                });
            }
        }

        return true;
    });

    return issues;
}

/** The level of the last heading that starts before `pos`, 1 when there is none. */
export function headingLevelBefore(doc: ProseNode, pos: number): number {
    let level = 1;

    doc.descendants((node, nodePos) => {
        if (node.type.name === 'heading' && nodePos < pos) {
            level = Number(node.attrs.level);
        }

        return true;
    });

    return level;
}

function headingIssueCount(doc: ProseNode): number {
    return documentIssues(doc).filter((issue) => issue.kind === 'heading')
        .length;
}

/**
 * FR-NEWS-005: a change that would put a heading more than one level below the one before it (H4 right
 * after H2) is not applied. Pasting is allowed: the editor then lists what to fix and the server
 * refuses the save until it is fixed.
 */
export const HeadingOrder = Extension.create({
    name: 'headingOrder',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('headingOrder'),
                filterTransaction: (transaction, state) => {
                    if (
                        !transaction.docChanged ||
                        transaction.getMeta('paste') ||
                        transaction.getMeta('uiEvent') === 'paste' ||
                        transaction.getMeta('preventUpdate')
                    ) {
                        return true;
                    }

                    return (
                        headingIssueCount(transaction.doc) <=
                        headingIssueCount(state.doc)
                    );
                },
            }),
        ];
    },
});

function numberAttribute(element: Element, name: string): number | null {
    const value = Number(element.getAttribute(name));

    return Number.isFinite(value) && value > 0 ? value : null;
}

/**
 * FR-NEWS-006: an image from the media library with its caption, stored as
 * `<figure><img data-asset-id alt …><figcaption>…</figcaption></figure>`. The server replaces the
 * `<img>` with the asset's responsive picture when the article is shown, so a replaced file shows
 * everywhere. `decorative` stores an empty alt on purpose (AC-NEWS-02).
 */
export const LibraryImage = Image.extend({
    group: 'block',
    inline: false,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
            alt: { default: '' },
            assetId: { default: null },
            decorative: { default: false },
            caption: { default: '' },
            width: { default: null },
            height: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure',
                getAttrs: (node) => {
                    const image = (node as HTMLElement).querySelector('img');

                    if (!image) {
                        return false;
                    }

                    return {
                        src: image.getAttribute('src'),
                        alt: image.getAttribute('alt') ?? '',
                        assetId: numberAttribute(image, 'data-asset-id'),
                        decorative:
                            image.getAttribute('data-decorative') === 'true',
                        caption:
                            (node as HTMLElement)
                                .querySelector('figcaption')
                                ?.textContent?.trim() ?? '',
                        width: numberAttribute(image, 'width'),
                        height: numberAttribute(image, 'height'),
                    };
                },
            },
            {
                tag: 'img[data-asset-id]',
                getAttrs: (node) => ({
                    src: (node as HTMLElement).getAttribute('src'),
                    alt: (node as HTMLElement).getAttribute('alt') ?? '',
                    assetId: numberAttribute(
                        node as HTMLElement,
                        'data-asset-id',
                    ),
                    decorative:
                        (node as HTMLElement).getAttribute(
                            'data-decorative',
                        ) === 'true',
                    width: numberAttribute(node as HTMLElement, 'width'),
                    height: numberAttribute(node as HTMLElement, 'height'),
                }),
            },
        ];
    },

    renderHTML({ node }) {
        const { src, alt, assetId, decorative, caption, width, height } =
            node.attrs;
        const image: Record<string, string> = { src: String(src ?? '') };

        image.alt = decorative ? '' : String(alt ?? '');

        if (assetId) {
            image['data-asset-id'] = String(assetId);
        }

        if (decorative) {
            image['data-decorative'] = 'true';
        }

        if (width) {
            image.width = String(width);
        }

        if (height) {
            image.height = String(height);
        }

        const parts: unknown[] = [['img', image]];

        if (caption) {
            parts.push(['figcaption', {}, String(caption)]);
        }

        return ['figure', {}, ...parts] as never;
    },
});
