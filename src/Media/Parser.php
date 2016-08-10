<?php

namespace MobileRider\Encoding\Media;

class Parser
{
    protected function prepareData($data)
    {
        if (!$data || is_array($data)) {
            return $data;
        }

        if ($data instanceof \SimpleXmlElement) {
            $data = new \SimpleXMLIterator($data->asXML());
        }

        if ($data instanceof \SimpleXMLIterator) {
            $data = sxi_to_array($data);
        } else if ($data instanceof \stdClass) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            throw new \Exception('Invalid data type');
        }

        return $data;
    }

    public static function parseMediaList($data)
    {
        $cleanData = [];
        $exemptKeys = ['mediaid', 'mediafile', 'mediastatus'];

        foreach ($data['media'] as $mediaData) {
            $cleanData[] = [
                'id' => $mediaData['mediaid'],
                'status' => $mediaData['mediastatus'],
                'sources' => [$mediaData['mediafile']],
                'properties' => array_diff_key($mediaData, $exemptKeys)
            ];
        }

        return $cleanData;
    }

    public static function parseMediaStatus($data, $extended = false)
    {
        if ($extended) {
            $data = $data['job'];
        }

        // Make it multiple
        if ($data && !isset($data[0])) {
            $data = [$data];
        }

        return $data;
    }

    public static function parseMediaInfo($rawData)
    {
        // Check if multiple sources exist
        if (isset($rawData['source'])) {
            $sources = $rawData['source'];
        } else {
            $sources = [$rawData];
        }

        foreach ($sources as &$sourceData) {
            // Extract values
            $sourceData['streams'] = [];

            $embeddedTrackTypes = ['video', 'audio'];
            $id = 1; // Assume 1 for video if exists and rest for audio

            // Check for embedded track properties in root file
            foreach ($embeddedTrackTypes as $index => $type) {
                $trackData = [];
                $prefix = $type . '_';

                foreach ($sourceData as $key => $value) {
                    if (strpos($key, $prefix) === 0) {
                        $trackData[str_replace($prefix, '', $key)] = $value;
                        unset($sourceData[$key]);
                    }
                }

                if ($trackData) {
                    $sourceData['streams'][$type][$id] = $trackData;
                    $id++;
                }
            }

            // Check for embedded stream data
            $embeddedStreamTypes = ['text'];

            foreach ($embeddedStreamTypes as $type) {
                $streamKey = $type . '_stream';

                if (array_key_exists($streamKey, $sourceData)) {
                    $sourceData['streams'][$type] = $rawData[$streamKey];
                    unset($sourceData[$streamKey]);
                }
            }
        }

        return $sources;
    }
}
