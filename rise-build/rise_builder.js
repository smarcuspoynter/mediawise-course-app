window.__RB = (() => {
const uid = () => crypto.randomUUID();
const now = () => new Date().toISOString();
const tpl = (v) => { const t = window.__tpl[v]; if (!t) throw new Error('no template ' + v); const b = JSON.parse(JSON.stringify(t)); b.id = uid(); b.globalBlockId = uid(); delete b.createdAt; delete b.updatedAt; return b; };
const H = window.__hdr;
function send(action) {
  return new Promise((res, rej) => {
    const x = new XMLHttpRequest();
    x.open('POST', '/api/rise-runtime/ducks/' + action.type);
    for (const k of Object.keys(H)) x.setRequestHeader(k, H[k]);
    x.onload = () => { let j = null; try { j = JSON.parse(x.responseText); } catch (e) {} (x.status < 300 ? res : rej)({ status: x.status, body: j, text: x.responseText.slice(0, 500) }); };
    x.onerror = () => rej({ status: 0 });
    x.send(JSON.stringify(action));
  });
}
function make(spec) {
  const [kind, a, b, c, d] = spec;
  let blk;
  switch (kind) {
    case 'p': blk = tpl('paragraph'); blk.items = [{ id: uid(), paragraph: a }]; break;
    case 'sub': blk = tpl('subheading'); blk.items = [{ id: uid(), heading: '<strong>' + a + '</strong>' }]; break;
    case 'hp': blk = tpl('heading paragraph'); blk.items = [{ id: uid(), heading: '<strong>' + a + '</strong>', paragraph: b }]; break;
    case 'bullets': blk = tpl('bulleted'); blk.items = a.map(t => ({ id: uid(), paragraph: '<p>' + t + '</p>' })); break;
    case 'numbered': blk = tpl('numbered'); blk.items = a.map(t => ({ id: uid(), paragraph: '<p>' + t + '</p>' })); break;
    case 'note': blk = tpl('note'); blk.items = [{ id: uid(), paragraph: a }]; break;
    case 'statement_b': blk = tpl('b'); blk.items = [{ id: uid(), paragraph: a }]; break;
    case 'statement_d': blk = tpl('d'); blk.items = [{ id: uid(), paragraph: a }]; break;
    case 'divider': blk = tpl('divider'); blk.items = []; break;
    case 'tabs': blk = tpl('tabs'); blk.items = a.map(([t, dsc]) => ({ id: uid(), title: t, description: dsc })); break;
    case 'accordion': blk = tpl('accordion'); blk.items = a.map(([t, dsc]) => ({ id: uid(), title: t, description: dsc })); break;
    case 'flash': blk = tpl('flashcard'); blk.items = a.map(([f, bk]) => ({ id: uid(), front: { type: 'description', description: f }, back: { type: 'description', description: bk } })); break;
    case 'sorting': blk = tpl('sorting'); blk.piles = a.map((t, i) => ({ id: i + 1, title: t })); blk.items = b.map(([t, p]) => ({ id: uid(), title: t, pileId: p + 1 })); break;
    case 'mc': blk = tpl('multiple choice'); blk.items = [{ id: uid(), type: 'MULTIPLE_CHOICE', title: a, answers: b.map(([t, ok]) => ({ id: uid(), title: t, correct: ok })), feedback: c }]; break;
    case 'timeline': blk = tpl('timeline'); blk.items = a.map(([dt, t, dsc]) => ({ id: uid(), date: dt, title: t, description: dsc })); break;
    case 'process': {
      blk = tpl('process');
      const intro = { id: uid(), type: 'intro', isHidden: false, title: a.title, description: a.desc };
      const steps = b.map(([t, dsc]) => ({ id: uid(), type: 'step', isHidden: false, title: t, description: dsc }));
      const sum = { id: uid(), type: 'summary', isHidden: false, title: c.title, description: c.desc };
      blk.items = [intro, ...steps, sum];
      break; }
    case 'scenario': {
      blk = tpl('scenario');
      const scene = blk.items[0]; scene.id = uid(); scene.title = 'Scene 1';
      if (scene.character) scene.character.name = a;
      const slides = b.map(s => ({ id: uid() }));
      scene.slides = b.map((s, i) => {
        const last = i === b.length - 1;
        if (s.type === 'text') return { id: slides[i].id, type: 'text', title: s.title, description: s.desc, emotion: 'neutral', goTo: last ? 'end' : 'next', hasCharacter: true, nextSlide: null, responses: [] };
        return { id: slides[i].id, type: 'dialogue', title: null, description: s.desc, emotion: 'neutral', goTo: 'next', hasCharacter: true, nextSlide: null,
          responses: s.responses.map(([t, ok, fb]) => ({ id: uid(), action: ok ? 'continue' : 'tryAgain', description: '<p>' + t + '</p>', feedback: fb, emotion: ok ? 'happy' : 'disappointed', goTo: 'next', nextSlide: null })) };
      });
      break; }
    default: throw new Error('unknown kind ' + kind);
  }
  return blk;
}
async function createBlocks(courseId, lessonId, specs, previousBlockId = null) {
  const blocks = specs.map(make);
  const r = await send({ type: 'rise/lessons/CREATE_BLOCKS', payload: { courseId, lessonId, previousBlockId, blocks } });
  return { r: r.status, n: blocks.length, lastId: blocks[blocks.length - 1].id };
}
async function deleteBlocks(courseId, lessonId, blockIds) {
  return (await send({ type: 'rise/lessons/DELETE_BLOCKS', payload: { courseId, lessonId, blockIds } })).status;
}
return { uid, send, make, createBlocks, deleteBlocks };
})();
'ok';
