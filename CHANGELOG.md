# CHANGELOG

## 1.5.5 - 2023-05-17

### FIXES
- fix: remove PriceFieldName attribute from search handler
  There is no need to send PriceFieldName attribute ("I" parameter in the
  request) in the API request.  The parameter "I" can be empty.
  It is enough to have the profile name configured in Celebros
  and it will defined the correct price field.
  
  Refs #SSUITE-893

## 1.5.4

### FIXES

- Fix Code sniffer errors
- Fix of empty response XML in Search Adapter and response parsers

## 1.5.3

### UPDATES

- Add Magento 2.4.4 and PHP 8.1 compatibility
- SSUITE-855: Add celebros.catalog.leftnav block to page layouts
- Refactor search engine classes
- SSUITE-856: Make sort by Relevance option a replacement for Position sorting on categories

### FIXES

- SSUITE-850: Fix Filter Search not searching all letters
- SSUITE-855: Fix sorting options in toolbar not switching order issue
- SSUITE-857: Fix sort order returned from Celebros now applies for all sorting options

## 1.5.2
- SSUITE-787: Change debug messages output
- Add Customer group name to principles request
- SSUITE-795: Fix IDs instead of label in layered navigation for Magento PWA
- SSUITE-811: Add extra answers in filters block for Magento PWA

## 1.5.1
- Tech fixes for GraphQl 
- refactoring

## 1.5.0
- Addf graphql has support

## 1.4.5
- Chage price filter labels format

## 1.4.4
- Change algorithm for nav2search method 'answer_id'

## 1.4.3
## 1.4.2
## 1.4.1
- di compilation fix

## 1.4.0
- Add php 7.4.x version constraint  in composer require block
- Tech fixes for swatch filter, price slider, js refactoring for Magento version >= 2.4.0

## 1.3.1
- Update cache lifetime 
- Remove popular search terms queries for celebros engine

## 1.3.0
- Add logs

## 1.2.4
- Fix issue with price filter

## 1.2.3
- Fix conflict with elasticsearch

## 1.2.2
- Add price filter sorting and filter search

## 1.2.1
- Fix sorting on search and category pages

## 1.2.0
- Move crossells/upsells festures support to module celebros/module-crossell

## 1.1.31
## 1.1.30
## 1.1.29
## 1.1.28
- Fix issue with duplicate attributes

## 1.1.27
## 1.1.26
## 1.1.25
## 1.1.24
## 1.1.23
## 1.1.22
## 1.1.21
## 1.1.20
## 1.1.19
- Add Magento 2.3.0 compatibility

## 1.1.18
- Change php  version constraint  in composer require block

## 1.1.17
- Add analytics for fallback requests

## 1.1.16
- Technical fixes for magento 2.2.4

## 1.1.15
- Add magefan/module-conflict-detector  to dependencies

## 1.1.14
- Fix sorting  for Magento 2.2.3

## 1.1.13
- Add 'custom message' campaign
## 1.1.12
## 1.1.11
## 1.1.10
## 1.1.9
## 1.1.8
## 1.1.7
- Fix profiles

## 1.1.6
- Fix  upsell products block on Magento Enterprise Edition
- 
## 1.1.5
## 1.1.4
## 1.1.3
## 1.1.2
## 1.1.1
## 1.1.0
- Add redirect to product page if single result is returned

## 1.0.0
Stable release
