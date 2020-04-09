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

on('click', '.js__plus-wp-block', (event) => {
  event.preventDefault()
  let parent = event.target.closest('.js__wp-block')
  addBlock('template__wp-block', parent, {})
})
