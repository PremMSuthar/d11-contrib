#!/usr/bin/env node
const { PurgeCSS } = require('purgecss');
const fs = require('fs');
const path = require('path');

async function runPurge() {
  const args = process.argv.slice(2);
  if (args.length < 2) {
    console.error('Usage: node purge-runner.js <config-file> <output-dir>');
    process.exit(1);
  }

  const configFile = args[0];
  const outputDir = args[1];

  try {
    const config = JSON.parse(fs.readFileSync(configFile, 'utf8'));
    
    const purgeCSSOptions = {
      content: config.content,
      css: config.css,
      whitelist: config.whitelist || [],
      whitelistPatterns: config.whitelistPatterns || [],
      extractors: config.extractors || [{
        extractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
        extensions: ['html', 'php', 'twig', 'js']
      }]
    };

    const results = await new PurgeCSS().purge(purgeCSSOptions);

    for (let i = 0; i < results.length; i++) {
      const result = results[i];
      const inputFile = config.css[i];
      const outputFile = path.join(outputDir, path.basename(inputFile));
      fs.writeFileSync(outputFile, result.css);
      
      const originalSize = fs.statSync(inputFile).size;
      const purgedSize = Buffer.byteLength(result.css, 'utf8');
      console.log(`${path.basename(inputFile)}: ${originalSize} -> ${purgedSize} bytes`);
    }

  } catch (error) {
    console.error('Error:', error.message);
    process.exit(1);
  }
}

runPurge();