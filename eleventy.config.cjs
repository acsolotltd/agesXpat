module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({
    "vendor": "vendor"
  });   
  eleventyConfig.addPassthroughCopy({
    "public": "."
  });
  eleventyConfig.addPassthroughCopy("src/assets");

  eleventyConfig.addPassthroughCopy("src/js");

  eleventyConfig.addFilter("date", function(dateObj) {
    // Standard JS formatting. Changes date objects to "YYYY-MM-DD"
    return new Date(dateObj).toISOString().split('T')[0];
  });


  return {
    dir: {
      input: "src",
      includes: "_includes",
      layouts: "_includes",
      output: "_site"
    },

    templateFormats: [
      "njk",
      "html",
      "md"
    ],

    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk"
  };
};
