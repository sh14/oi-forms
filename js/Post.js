/**
 * Adding content block
 *
 * @param templateId - string
 * @param element - Element object
 * @param data - Data object
 */
function addBlock (templateId, element, data) {
  let template = document.getElementById(templateId)

  // if there is no template or element then exit
  if (!template || !element) return

  // convert HTML to DOM node
  template = htmlToNode(tmpl(template.innerHTML, data))

  // insert new block after the element
  element.parentNode.insertBefore(template, element.nextSibling)

  // scroll to new block
  scrollCloseTo(template)
}

/**
 * Reindexing all content blocks
 */
function reIndexContent () {
  // select all blocks
  let blocks = document.querySelectorAll('.js__wp-block')
  // loop blocks
  blocks.forEach((el, index) => {
    // set names of fields contained in each block
    let names = ['block_options', 'block_type', 'block_content',]
    // loop for fields
    for (const name of names) {
      // search for current field
      let field = el.querySelector('[data-name="' + name + '"]')
      // set attributes for current field
      field.setAttribute('id', name + '-' + index)
      field.setAttribute('name', name + '[' + index + ']')
    }
  })
}

// reindex all blocks
reIndexContent()

// Listen for click on a plus button
on('click', '.js__plus-wp-block', (event) => {
  event.preventDefault()
  // search for root block element
  let parent = event.target.closest('.js__wp-block')
  // add new block after selected block
  addBlock('template__wp-block', parent, {})
  // reindex all blocks
  reIndexContent()
})
