<?php

namespace pragmatic\seo\controllers;

use Craft;
use craft\db\Query;
use craft\elements\Asset;
use craft\web\Controller;
use pragmatic\seo\fields\SeoField;
use yii\web\Response;

class DefaultController extends Controller
{
    private const ASSET_META_TABLE = '{{%pragmaticseo_asset_meta}}';
    protected int|bool|array $allowAnonymous = false;

    public function actionIndex(): Response
    {
        return $this->redirect('pragmatic-seo/general');
    }

    public function actionGeneral(): Response
    {
        return $this->renderTemplate('pragmatic-seo/general');
    }

    public function actionOptions(): Response
    {
        return $this->renderTemplate('pragmatic-seo/options');
    }

    public function actionImages(): Response
    {
        $this->ensureAssetMetaTable();
        $usedOnly = Craft::$app->getRequest()->getQueryParam('used') === '1';

        $assets = Asset::find()
            ->kind('image')
            ->status(null)
            ->siteId(Craft::$app->getSites()->getCurrentSite()->id)
            ->limit(null)
            ->all();

        $assetIds = array_map(fn(Asset $asset) => (int)$asset->id, $assets);
        $usedIds = $this->getUsedAssetIds($assetIds);

        $altRows = (new Query())
            ->select(['assetId', 'altText'])
            ->from(self::ASSET_META_TABLE)
            ->all();
        $altByAssetId = [];
        foreach ($altRows as $row) {
            $altByAssetId[(int)$row['assetId']] = (string)($row['altText'] ?? '');
        }

        $rows = [];
        foreach ($assets as $asset) {
            $isUsed = in_array((int)$asset->id, $usedIds, true);
            if ($usedOnly && !$isUsed) {
                continue;
            }

            $rows[] = [
                'asset' => $asset,
                'isUsed' => $isUsed,
                'altText' => $altByAssetId[(int)$asset->id] ?? '',
            ];
        }

        return $this->renderTemplate('pragmatic-seo/images', [
            'rows' => $rows,
            'usedOnly' => $usedOnly,
        ]);
    }

    public function actionContent(): Response
    {
        $seoFields = array_values(array_filter(
            Craft::$app->getFields()->getAllFields(),
            fn($field) => $field instanceof SeoField
        ));

        return $this->renderTemplate('pragmatic-seo/content', [
            'seoFields' => $seoFields,
        ]);
    }

    public function actionSaveContent(): Response
    {
        $this->requirePostRequest();
        $fieldsData = Craft::$app->getRequest()->getBodyParam('fields', []);
        $fieldsService = Craft::$app->getFields();

        foreach ($fieldsData as $fieldId => $data) {
            $field = $fieldsService->getFieldById((int)$fieldId);
            if (!$field instanceof SeoField) {
                continue;
            }

            $field->defaultTitle = trim((string)($data['title'] ?? ''));
            $field->defaultDescription = trim((string)($data['description'] ?? ''));
            $field->defaultImageId = !empty($data['imageId']) ? (int)$data['imageId'] : null;
            $field->defaultImageDescription = trim((string)($data['imageDescription'] ?? ''));

            $fieldsService->saveField($field);
        }

        Craft::$app->getSession()->setNotice('Contenido SEO guardado.');
        return $this->redirect('pragmatic-seo/content');
    }

    public function actionSaveImages(): Response
    {
        $this->requirePostRequest();
        $this->ensureAssetMetaTable();

        $assetsData = Craft::$app->getRequest()->getBodyParam('assets', []);
        $db = Craft::$app->getDb();
        $elements = Craft::$app->getElements();

        foreach ($assetsData as $assetId => $data) {
            $asset = Asset::find()
                ->id((int)$assetId)
                ->status(null)
                ->siteId(Craft::$app->getSites()->getCurrentSite()->id)
                ->one();
            if (!$asset) {
                continue;
            }

            $title = trim((string)($data['title'] ?? ''));
            if ($title !== '' && $title !== $asset->title) {
                $asset->title = $title;
                $elements->saveElement($asset, false, false, false);
            }

            $altText = trim((string)($data['altText'] ?? ''));
            $db->createCommand()->upsert(self::ASSET_META_TABLE, [
                'assetId' => (int)$assetId,
                'altText' => $altText,
            ], [
                'altText' => $altText,
            ])->execute();
        }

        Craft::$app->getSession()->setNotice('Imagenes guardadas.');
        return $this->redirectToPostedUrl();
    }

    private function getUsedAssetIds(array $assetIds): array
    {
        if (empty($assetIds)) {
            return [];
        }

        return array_map('intval', (new Query())
            ->select(['targetId'])
            ->distinct()
            ->from('{{%relations}}')
            ->where(['targetId' => $assetIds])
            ->column());
    }

    private function ensureAssetMetaTable(): void
    {
        $db = Craft::$app->getDb();
        if ($db->tableExists(self::ASSET_META_TABLE)) {
            return;
        }

        $db->createCommand()->createTable(self::ASSET_META_TABLE, [
            'assetId' => 'integer NOT NULL PRIMARY KEY',
            'altText' => 'text',
        ])->execute();

        $db->createCommand()->addForeignKey(
            null,
            self::ASSET_META_TABLE,
            ['assetId'],
            '{{%assets}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        )->execute();
    }
}
